<?php

namespace App\Livewire;

use App\Enums\Motif;
use App\Enums\Repas;
use App\Exceptions\ReservationException;
use App\Http\Requests\Reservation\FacturationRequest;
use App\Http\Requests\Reservation\IdentiteRequest;
use App\Http\Requests\Reservation\SejourRequest;
use App\Models\Chambre;
use App\Models\Client;
use App\Models\Organisme;
use App\Models\Parametre;
use App\Models\TarifRepas;
use App\Services\AvailabilityService;
use App\Services\HoldService;
use App\Services\OtpService;
use App\Services\PricingService;
use App\Services\ReservationService;
use App\Support\Telephone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Formulaire de réservation en 3 fenêtres (docs/04-parcours-et-api.md).
 * La chambre est choisie avant : elle est verrouillée par un hold de 15 minutes dès l'ouverture.
 *
 * @property-read Chambre|null $chambre
 * @property-read array<string, mixed>|null $devis
 */
class ReservationModal extends Component
{
    public bool $ouvert = false;

    /** 1 à 3 : fenêtres ; 4 : confirmation ; 0 : hold perdu / chambre indisponible. */
    public int $etape = 1;

    public ?int $chambreId = null;

    public string $arrivee = '';

    public string $depart = '';

    #[Locked]
    public string $holdJeton = '';

    #[Locked]
    public int $holdExpire = 0;

    public string $erreur = '';

    // Fenêtre 1
    public string $telephone = '';

    /** idle | invalide | nouveau | connu | verifie */
    public string $etatTelephone = 'idle';

    public string $code = '';

    public string $messageCode = '';

    public string $nom = '';

    public string $prenom = '';

    public string $type_piece = 'Passeport';

    public string $numero_piece = '';

    public string $piece_delivree_le = '';

    public string $email = '';

    // Fenêtre 2
    public string $motif = 'mission';

    public string $heure = '14:00';

    public bool $mangeSurPlace = false;

    /** @var list<string> */
    public array $repas = [];

    // Fenêtre 3
    public string $correspondant_nom = '';

    public string $correspondant_tel = '';

    public string $payeur = 'client';

    public ?int $organisme_id = null;

    public bool $conditions = false;

    public string $signature = '';

    // Confirmation
    public string $numero = '';

    public string $lienSuivi = '';

    public string $lienFiche = '';

    #[On('reservation-ouvrir')]
    public function ouvrir(int $chambre, string $arrivee, string $depart, HoldService $holds): void
    {
        $this->reinitialiser();
        $this->chambreId = $chambre;
        $this->arrivee = $arrivee;
        $this->depart = $depart;
        $this->ouvert = true;

        try {
            $this->validate([
                'chambreId' => SejourRequest::regles()['chambre_id'],
                'arrivee' => SejourRequest::regles()['arrivee'],
                'depart' => SejourRequest::regles()['depart'],
            ]);
        } catch (ValidationException $e) {
            $this->resetErrorBag();
            $this->perdreChambre((string) collect($e->errors())->flatten()->first());

            return;
        }

        if (! $this->limiter('holds', 20)) {
            $this->perdreChambre(__('reservation.erreurs.trop_de_demandes'));

            return;
        }

        $this->poserHold($holds);
    }

    public function fermer(HoldService $holds): void
    {
        $holds->liberer($this->holdJeton);
        $this->reinitialiser();
        $this->ouvert = false;
    }

    /** Appelé par le minuteur du navigateur à l'expiration du hold. */
    public function expirer(HoldService $holds): void
    {
        if ($this->etape === 4 || $this->holdExpire > now()->getTimestamp()) {
            return;
        }
        $holds->liberer($this->holdJeton);
        $this->perdreChambre(__('reservation.modal.hold_expire'));
    }

    public function verifierTelephone(OtpService $otp): void
    {
        $this->code = '';
        $this->messageCode = '';
        $this->etatTelephone = 'idle';

        if (trim($this->telephone) === '') {
            return;
        }
        $normalise = Telephone::normaliser($this->telephone);
        if ($normalise === null) {
            $this->etatTelephone = 'invalide';

            return;
        }
        if (! $this->limiter('lookup', 20)) {
            $this->messageCode = __('reservation.erreurs.trop_de_demandes');

            return;
        }

        $this->telephone = $normalise;
        if (Telephone::estVerifie($normalise) && ($client = Client::query()->where('telephone', $normalise)->first())) {
            $this->preremplir($client);
            $this->etatTelephone = 'verifie';

            return;
        }

        // Aucune donnée personnelle n'est renvoyée tant que le code n'est pas validé.
        if (Client::query()->where('telephone', $normalise)->exists()) {
            $this->etatTelephone = 'connu';
            $this->envoyerCode($otp);
        } else {
            $this->etatTelephone = 'nouveau';
        }
    }

    public function renvoyerCode(OtpService $otp): void
    {
        if ($this->etatTelephone === 'connu') {
            $this->envoyerCode($otp);
        }
    }

    public function verifierCode(OtpService $otp): void
    {
        if ($this->etatTelephone !== 'connu') {
            return;
        }
        if (! $this->limiter('otp-verification', 10, 300)) {
            $this->messageCode = __('reservation.erreurs.trop_de_demandes');

            return;
        }

        $resultat = $otp->verifier($this->telephone, trim($this->code));
        if ($resultat !== OtpService::OK) {
            $this->messageCode = __('reservation.erreurs.otp_'.$resultat);
            $this->code = '';

            return;
        }

        $client = Client::query()->where('telephone', $this->telephone)->first();
        if ($client) {
            $this->preremplir($client);
        }
        $this->etatTelephone = 'verifie';
        $this->messageCode = '';
        $this->code = '';
    }

    public function continuer(HoldService $holds): void
    {
        $this->erreur = '';
        if (! $this->holdActif($holds)) {
            return;
        }

        if ($this->etape === 1) {
            if (! in_array($this->etatTelephone, ['nouveau', 'verifie'], true)) {
                $this->addError('telephone', $this->etatTelephone === 'connu'
                    ? __('reservation.erreurs.verification')
                    : __('reservation.modal.e1.invalide'));

                return;
            }
            $this->validate(IdentiteRequest::regles());
            $this->etape = 2;
        } elseif ($this->etape === 2) {
            $this->validate($this->reglesSejour());
            if (! $this->synchroniserHold($holds)) {
                return;
            }
            if ($this->devis === null) {
                $this->erreur = __('reservation.erreurs.indisponible');

                return;
            }
            $this->etape = 3;
        }
    }

    public function retour(): void
    {
        $this->erreur = '';
        if ($this->etape > 1 && $this->etape < 4) {
            $this->etape--;
        }
    }

    public function choisirRepas(bool $oui): void
    {
        $this->mangeSurPlace = $oui;
        if (! $oui) {
            $this->repas = [];
        }
    }

    public function basculerRepas(string $repas): void
    {
        if (Repas::tryFrom($repas) === null) {
            return;
        }
        $this->repas = in_array($repas, $this->repas, true)
            ? array_values(array_diff($this->repas, [$repas]))
            : [...$this->repas, $repas];
    }

    public function confirmer(HoldService $holds, ReservationService $reservations): void
    {
        // Idempotence : un second clic (étape déjà passée à 4) ne crée rien.
        if ($this->etape !== 3) {
            return;
        }
        $this->erreur = '';
        $this->validate(FacturationRequest::regles());
        $this->validate($this->reglesSejour());
        $this->validate(IdentiteRequest::regles());

        if (! $this->limiter('reservations', 5)) {
            $this->erreur = __('reservation.erreurs.trop_de_demandes');

            return;
        }

        try {
            $resultat = $reservations->creer([
                'telephone' => (string) Telephone::normaliser($this->telephone),
                'identite' => $this->identite(),
                'sejour' => [
                    'chambre_id' => (int) $this->chambreId,
                    'motif' => $this->motif,
                    'arrivee' => $this->arrivee,
                    'heure' => $this->heure,
                    'depart' => $this->depart,
                    'repas' => $this->repas,
                ],
                'facturation' => [
                    'correspondant_nom' => $this->correspondant_nom ?: null,
                    'correspondant_tel' => $this->correspondant_tel ?: null,
                    'payeur' => $this->payeur,
                    'organisme_id' => $this->payeur === 'organisme' ? $this->organisme_id : null,
                ],
                'signature' => trim($this->signature),
                'idempotence' => HoldService::empreinte($this->holdJeton),
            ], $this->holdJeton);
        } catch (ReservationException $e) {
            if (in_array($e->raison, [ReservationException::HOLD_EXPIRE, ReservationException::INDISPONIBLE], true)) {
                $this->perdreChambre($e->getMessage());
            } elseif ($e->raison === ReservationException::VERIFICATION) {
                $this->etape = 1;
                $this->etatTelephone = 'connu';
                $this->erreur = $e->getMessage();
            } else {
                $this->erreur = $e->getMessage();
            }

            return;
        }

        $reservation = $resultat['reservation'];
        $this->numero = $reservation->numero;
        $this->lienSuivi = $resultat['jeton'] ? route('suivi', $resultat['jeton']) : '';
        $this->lienFiche = URL::temporarySignedRoute('fiche.pdf', now()->addHours(2), ['numero' => $reservation->numero]);
        $this->holdJeton = '';
        $this->etape = 4;
    }

    #[Computed]
    public function chambre(): ?Chambre
    {
        return $this->chambreId
            ? Chambre::query()->with(['site.ville', 'type'])->find($this->chambreId)
            : null;
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function devis(): ?array
    {
        if (! $this->chambre || $this->arrivee === '' || $this->depart === '' || ! preg_match('/^\d{2}:\d{2}$/', $this->heure)) {
            return null;
        }

        try {
            return app(ReservationService::class)->estimer($this->chambre, $this->arrivee, $this->heure, $this->depart, $this->repas);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        $restant = $this->holdExpire > 0 ? max(0, $this->holdExpire - now()->getTimestamp()) : 0;

        return view('livewire.reservation-modal', [
            'motifs' => Motif::options(),
            'restant' => $restant,
            'tarifsRepas' => TarifRepas::query()->get()->keyBy(fn ($t) => $t->repas->value),
            'organismes' => $this->etape === 3 ? Organisme::query()->orderBy('nom')->get() : collect(),
            'heureDepart' => sprintf('%02dh00', app(AvailabilityService::class)->heureDepart()),
            'delaiPaiement' => (int) Parametre::valeur('delai_paiement_heures', config('uam.payment_deadline_hours')),
            'tauxTva' => rtrim(rtrim(number_format(app(PricingService::class)->tauxTva(), 2, ',', ''), '0'), ','),
            'nuitees' => $this->devis['nuitees'] ?? 0,
        ]);
    }

    private function poserHold(HoldService $holds): void
    {
        try {
            ['hold' => $hold, 'jeton' => $jeton] = $holds->creer(
                (int) $this->chambreId, $this->arrivee, $this->depart, null, $this->holdJeton ?: null,
            );
        } catch (ReservationException $e) {
            $this->perdreChambre($e->getMessage());

            return;
        }
        $this->holdJeton = $jeton;
        $this->holdExpire = $hold->expire_le->getTimestamp();
    }

    /** Si les dates ont changé en fenêtre 2, le hold est reposé sur les nouvelles dates. */
    private function synchroniserHold(HoldService $holds): bool
    {
        $hold = $holds->valider($this->holdJeton);
        if ($hold && $hold->arrivee->toDateString() === $this->arrivee && $hold->depart->toDateString() === $this->depart) {
            return true;
        }

        $this->poserHold($holds);

        return $this->etape !== 0;
    }

    private function holdActif(HoldService $holds): bool
    {
        if ($holds->valider($this->holdJeton) !== null) {
            return true;
        }
        $this->perdreChambre(__('reservation.modal.hold_expire'));

        return false;
    }

    private function perdreChambre(string $message): void
    {
        $this->etape = 0;
        $this->holdJeton = '';
        $this->holdExpire = 0;
        $this->erreur = $message;
    }

    private function envoyerCode(OtpService $otp): void
    {
        if (! $this->limiter('otp-envoi', 3, 600)) {
            $this->messageCode = __('reservation.erreurs.otp_trop_d_envois');

            return;
        }
        $this->messageCode = $otp->envoyer($this->telephone) ? '' : __('reservation.erreurs.otp_trop_d_envois');
    }

    private function preremplir(Client $client): void
    {
        $this->nom = $client->nom;
        $this->prenom = $client->prenom;
        $this->type_piece = $client->type_piece ?? '';
        $this->numero_piece = $client->numero_piece ?? '';
        $this->piece_delivree_le = $client->piece_delivree_le?->toDateString() ?? '';
        $this->email = $client->email ?? '';
    }

    /** @return array<string, mixed> */
    private function identite(): array
    {
        return [
            'nom' => trim($this->nom),
            'prenom' => trim($this->prenom),
            'type_piece' => $this->type_piece ?: null,
            'numero_piece' => $this->numero_piece ?: null,
            'piece_delivree_le' => $this->piece_delivree_le ?: null,
            'email' => $this->email ?: null,
        ];
    }

    /** Règles de la fenêtre 2 appliquées aux propriétés du composant. */
    private function reglesSejour(): array
    {
        $regles = SejourRequest::regles();

        return [
            'chambreId' => $regles['chambre_id'],
            'motif' => $regles['motif'],
            'arrivee' => $regles['arrivee'],
            'heure' => $regles['heure'],
            'depart' => $regles['depart'],
            'repas' => $regles['repas'],
            'repas.*' => $regles['repas.*'],
        ];
    }

    /** Limite de débit par adresse IP, pour le chemin Livewire (équivalent des limiteurs des routes JSON). */
    private function limiter(string $nom, int $max, int $secondes = 60): bool
    {
        $cle = 'modal:'.$nom.':'.request()->ip();

        return RateLimiter::attempt($cle, $max, fn () => true, $secondes);
    }

    private function reinitialiser(): void
    {
        $this->reset([
            'etape', 'chambreId', 'arrivee', 'depart', 'holdJeton', 'holdExpire', 'erreur', 'telephone', 'etatTelephone',
            'code', 'messageCode', 'nom', 'prenom', 'type_piece', 'numero_piece', 'piece_delivree_le', 'email',
            'motif', 'heure', 'mangeSurPlace', 'repas', 'correspondant_nom', 'correspondant_tel', 'payeur',
            'organisme_id', 'conditions', 'signature', 'numero', 'lienSuivi', 'lienFiche',
        ]);
        $this->resetErrorBag();
        unset($this->chambre, $this->devis);
    }
}
