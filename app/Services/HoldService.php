<?php

namespace App\Services;

use App\Exceptions\ReservationException;
use App\Models\Chambre;
use App\Models\ChambreHold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Holds temporaires de chambre (RG13) : 15 minutes, revérification de la disponibilité sous verrou.
 * Le jeton remis au navigateur n'est jamais stocké en clair (seule son empreinte l'est).
 */
class HoldService
{
    public function __construct(private readonly AvailabilityService $disponibilites) {}

    public static function empreinte(string $jeton): string
    {
        return hash('sha256', $jeton);
    }

    /**
     * Pose un hold (en remplaçant celui du même navigateur, s'il existe).
     *
     * @return array{hold: ChambreHold, jeton: string}
     *
     * @throws ReservationException si la chambre n'est plus disponible
     */
    public function creer(int $chambreId, string $arrivee, string $depart, ?string $telephone = null, ?string $jetonExistant = null): array
    {
        return DB::transaction(function () use ($chambreId, $arrivee, $depart, $telephone, $jetonExistant) {
            // Verrou sur la chambre : deux demandes simultanées sont sérialisées.
            $chambre = Chambre::query()->whereKey($chambreId)->lockForUpdate()->first();
            if ($chambre === null) {
                throw new ReservationException(__('reservation.erreurs.indisponible'), ReservationException::INDISPONIBLE);
            }

            $empreinteExistante = $jetonExistant ? self::empreinte($jetonExistant) : null;
            // Anti-saturation : un visiteur ne peut pas bloquer toutes les chambres (hors celle qu'il remplace).
            $ipHash = hash('sha256', (string) request()->ip().config('app.key'));
            $autres = ChambreHold::query()->actifs()->where('ip_hash', $ipHash)
                ->when($empreinteExistante, fn ($q) => $q->where('token', '!=', $empreinteExistante))->count();
            if ($autres >= config('uam.max_holds_par_ip')) {
                throw new ReservationException(__('reservation.erreurs.trop_de_holds'), ReservationException::INDISPONIBLE);
            }

            if (! $this->disponibilites->estDisponible($chambre, $arrivee, $depart, $empreinteExistante)) {
                throw new ReservationException(__('reservation.erreurs.indisponible'), ReservationException::INDISPONIBLE);
            }

            if ($empreinteExistante) {
                ChambreHold::query()->where('token', $empreinteExistante)->delete();
            }

            $jeton = Str::random(40);
            $hold = ChambreHold::query()->create([
                'chambre_id' => $chambre->id,
                'telephone' => $telephone,
                'ip_hash' => $ipHash,
                'arrivee' => $arrivee,
                'depart' => $depart,
                'expire_le' => now()->addMinutes(config('uam.hold_ttl_minutes')),
                'token' => self::empreinte($jeton),
            ]);

            return ['hold' => $hold, 'jeton' => $jeton];
        });
    }

    /** Hold actif correspondant au jeton, ou null (inconnu ou expiré). */
    public function valider(?string $jeton): ?ChambreHold
    {
        if (! $jeton) {
            return null;
        }

        return ChambreHold::query()->actifs()->where('token', self::empreinte($jeton))->first();
    }

    public function liberer(?string $jeton): void
    {
        if ($jeton) {
            ChambreHold::query()->where('token', self::empreinte($jeton))->delete();
        }
    }

    /** Supprime les holds expirés (planifié chaque minute). */
    public function purger(): int
    {
        return ChambreHold::query()->where('expire_le', '<=', now())->delete();
    }
}
