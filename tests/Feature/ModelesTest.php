<?php

use App\Enums\StatutReservation;
use App\Models\AuditLog;
use App\Models\Chambre;
use App\Models\Facture;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\Sequence;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\TypeChambre;
use App\Models\Ville;
use Illuminate\Database\QueryException;

it('rattache une chambre à un site et un site à une ville (RG01)', function () {
    $chambre = Chambre::factory()->create();

    expect($chambre->site)->toBeInstanceOf(Site::class)
        ->and($chambre->site->ville)->toBeInstanceOf(Ville::class)
        ->and($chambre->type)->toBeInstanceOf(TypeChambre::class);
});

it('interdit deux chambres au même numéro sur un site', function () {
    $site = Site::factory()->create();
    Chambre::factory()->create(['site_id' => $site->id, 'numero' => '01']);

    expect(fn () => Chambre::factory()->create(['site_id' => $site->id, 'numero' => '01']))
        ->toThrow(QueryException::class);
});

it('détecte les chevauchements de réservations actives sur [arrivée, départ[ (RG02)', function () {
    $chambre = Chambre::factory()->create();
    Reservation::factory()->create([
        'chambre_id' => $chambre->id,
        'arrivee' => '2026-12-10 14:00', 'depart' => '2026-12-12 10:00',
    ]);

    $chevauche = fn (string $a, string $d) => Reservation::chevauchant($chambre->id, $a, $d)->exists();

    expect($chevauche('2026-12-11 14:00', '2026-12-13 10:00'))->toBeTrue()
        ->and($chevauche('2026-12-12 10:00', '2026-12-14 10:00'))->toBeFalse() // départ exclu
        ->and($chevauche('2026-12-08 10:00', '2026-12-10 14:00'))->toBeFalse();
});

it('ignore les réservations terminées ou annulées dans les chevauchements', function () {
    $chambre = Chambre::factory()->create();
    Reservation::factory()->statut(StatutReservation::Annulee)->create([
        'chambre_id' => $chambre->id,
        'arrivee' => '2026-12-10 14:00', 'depart' => '2026-12-12 10:00',
    ]);

    expect(Reservation::chevauchant($chambre->id, '2026-12-10 14:00', '2026-12-12 10:00')->exists())->toBeFalse();
});

it('applique les transitions de statut du workflow', function () {
    expect(StatutReservation::EnAttente->peutPasserA(StatutReservation::Confirmee))->toBeTrue()
        ->and(StatutReservation::EnAttente->peutPasserA(StatutReservation::EnCours))->toBeFalse()
        ->and(StatutReservation::Confirmee->peutPasserA(StatutReservation::NonPresente))->toBeTrue()
        ->and(StatutReservation::EnCours->peutPasserA(StatutReservation::Terminee))->toBeTrue()
        ->and(StatutReservation::Terminee->transitionsPossibles())->toBe([]);
});

it('choisit le tarif de la chambre avant celui du type, selon la date (RG06)', function () {
    $chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $chambre->type_chambre_id, 'prix_nuit' => 15000, 'date_debut' => '2026-01-01']);

    expect($chambre->tarifA(now()->setDate(2026, 6, 1))->prix_nuit)->toBe(15000)
        ->and($chambre->tarifA(now()->setDate(2025, 6, 1)))->toBeNull();

    Tarif::factory()->create(['type_chambre_id' => null, 'chambre_id' => $chambre->id, 'prix_nuit' => 20000, 'date_debut' => '2026-03-01']);

    expect($chambre->tarifA(now()->setDate(2026, 6, 1))->prix_nuit)->toBe(20000)
        ->and($chambre->tarifA(now()->setDate(2026, 2, 1))->prix_nuit)->toBe(15000);
});

it('génère une séquence sans trou par clé et par année (RG03)', function () {
    expect(Sequence::suivante('fiche', 2026))->toBe(1)
        ->and(Sequence::suivante('fiche', 2026))->toBe(2)
        ->and(Sequence::suivante('fiche', 2027))->toBe(1)
        ->and(Sequence::suivante('facture', 2026))->toBe(1);
});

it('rend une facture émise immuable et non supprimable (RG12)', function () {
    $facture = Facture::factory()->create();

    expect(fn () => $facture->update(['montant_ttc' => 1]))->toThrow(LogicException::class)
        ->and(fn () => $facture->delete())->toThrow(LogicException::class);

    $facture = $facture->fresh();
    $facture->update(['pdf_path' => 'factures/x.pdf']);
    expect($facture->fresh()->pdf_path)->toBe('factures/x.pdf');
});

it('lit les paramètres avec valeur par défaut', function () {
    Parametre::create(['cle' => 'tva', 'valeur' => 18]);

    expect(Parametre::valeur('tva'))->toBe(18)
        ->and(Parametre::valeur('inconnu', 'x'))->toBe('x');
});

it('journalise les modifications dans audit_logs', function () {
    $ville = Ville::factory()->create();
    $ville->update(['nom' => 'Nouveau nom']);

    expect(AuditLog::where('subject_type', Ville::class)->where('subject_id', $ville->id)->count())->toBe(2);
});
