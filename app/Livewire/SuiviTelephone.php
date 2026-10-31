<?php

namespace App\Livewire;

use App\Exceptions\ReservationException;
use App\Models\Reservation;
use App\Services\OtpService;
use App\Services\ReservationService;
use App\Support\Telephone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** « Retrouver une réservation » : téléphone + code SMS, sans compte. */
class SuiviTelephone extends Component
{
    public string $telephone = '';

    public string $code = '';

    /** telephone | code | liste */
    #[Locked]
    public string $etape = 'telephone';

    #[Locked]
    public string $telephoneVerifie = '';

    public string $message = '';

    public function envoyerCode(OtpService $otp): void
    {
        $this->message = '';
        $normalise = Telephone::normaliser($this->telephone);
        if ($normalise === null) {
            $this->message = __('reservation.modal.e1.invalide');

            return;
        }
        if (! $this->limiter('suivi-envoi', 5, 600)) {
            $this->message = __('reservation.erreurs.trop_de_demandes');

            return;
        }

        // Même réponse pour un numéro inconnu : on n'indique pas s'il existe.
        $this->telephone = $normalise;
        $this->message = $otp->envoyer($normalise) ? __('reservation.suivi.code_envoye') : __('reservation.erreurs.otp_trop_d_envois');
        $this->etape = 'code';
        $this->code = '';
    }

    public function verifier(OtpService $otp): void
    {
        if ($this->etape !== 'code') {
            return;
        }
        if (! $this->limiter('suivi-verif', 10, 300)) {
            $this->message = __('reservation.erreurs.trop_de_demandes');

            return;
        }

        $resultat = $otp->verifier($this->telephone, trim($this->code));
        if ($resultat !== OtpService::OK) {
            $this->message = __('reservation.erreurs.otp_'.$resultat);
            $this->code = '';

            return;
        }

        $this->telephoneVerifie = $this->telephone;
        $this->etape = 'liste';
        $this->message = '';
    }

    public function annuler(int $reservationId, ReservationService $reservations): void
    {
        $reservation = $this->reservations()->firstWhere('id', $reservationId);
        if ($reservation === null) {
            return;
        }

        try {
            $reservations->annuler($reservation);
            $this->message = __('reservation.suivi.annulee');
        } catch (ReservationException $e) {
            $this->message = $e->getMessage();
        }
    }

    public function quitter(): void
    {
        if ($this->telephoneVerifie !== '') {
            Telephone::oublier($this->telephoneVerifie);
        }
        $this->reset(['telephone', 'code', 'etape', 'telephoneVerifie', 'message']);
    }

    public function render(): View
    {
        $liste = collect();
        if ($this->etape === 'liste') {
            // La preuve OTP doit toujours être valable (session) pour afficher des données personnelles.
            if (Telephone::estVerifie($this->telephoneVerifie)) {
                $liste = $this->reservations()->map(function (Reservation $r) {
                    $r->setAttribute('lien_fiche', URL::temporarySignedRoute('fiche.pdf', now()->addMinutes(30), ['numero' => $r->numero]));

                    return $r;
                });
            } else {
                $this->quitter();
            }
        }

        return view('livewire.suivi-telephone', ['liste' => $liste]);
    }

    /** @return Collection<int, Reservation> */
    private function reservations()
    {
        if ($this->telephoneVerifie === '' || ! Telephone::estVerifie($this->telephoneVerifie)) {
            return collect();
        }

        return Reservation::query()
            ->with(['chambre.site', 'chambre.type'])
            ->whereHas('client', fn ($q) => $q->where('telephone', $this->telephoneVerifie))
            ->orderByDesc('id')
            ->get();
    }

    private function limiter(string $nom, int $max, int $secondes): bool
    {
        return RateLimiter::attempt('modal:'.$nom.':'.request()->ip(), $max, fn () => true, $secondes);
    }
}
