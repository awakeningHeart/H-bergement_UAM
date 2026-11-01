<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $facture->type->libelle() }} {{ $facture->numero }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #121722; }
        h1 { font-size: 20px; margin: 0; color: #0a1b3d; }
        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #0b4ea2; margin: 18px 0 6px; border-bottom: 1px solid #e2d9c8; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 0; vertical-align: top; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7383; border-bottom: 1px solid #0a1b3d; }
        td.r, th.r { text-align: right; }
        .lignes td { border-bottom: 1px solid #ece5d8; }
        .num { font-size: 15px; font-weight: bold; color: #e30613; }
        .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #0a1b3d; }
        .petit { font-size: 9px; color: #4d5566; line-height: 1.5; }
        .avoir { color: #e30613; }
    </style>
</head>
<body>
@php
    $f = \App\Support\Format::class;
    $estAvoir = $facture->type === \App\Enums\TypeFacture::Avoir;
    $tz = config('uam.timezone');
    $e = $d['etablissement'] ?? [];
    $a = $d['facture_a'] ?? [];
    $r = $d['reservation'] ?? [];
    $propre = fn ($v) => is_string($v) && ! str_contains($v, 'À renseigner') ? $v : null;
@endphp
<table>
    <tr>
        <td style="width:45%"><img src="{{ $logo }}" alt="Hébergement UAM" style="height:56px"></td>
        <td style="text-align:right">
            <h1 @class(['avoir' => $estAvoir])>{{ $estAvoir ? 'AVOIR' : 'FACTURE' }}</h1>
            <div class="num">{{ $facture->numero }}</div>
            <div class="petit">Émise le {{ $facture->emise_le->setTimezone($tz)->format('d/m/Y') }}
                @if (! empty($d['facture_origine'])) — annule la facture {{ $d['facture_origine'] }}@endif</div>
        </td>
    </tr>
</table>

<table style="margin-top:14px">
    <tr>
        <td style="width:50%">
            <h2>Émetteur</h2>
            <div>{{ $propre($e['nom'] ?? null) ?? 'Hébergement UAM' }}</div>
            @if ($propre($e['adresse'] ?? null))<div>{{ $e['adresse'] }}</div>@endif
            @if ($propre($e['telephone'] ?? null))<div>Tél. {{ $e['telephone'] }}</div>@endif
            @if ($propre($e['email'] ?? null))<div>{{ $e['email'] }}</div>@endif
            @if ($propre($e['nif'] ?? null))<div>NIF {{ $e['nif'] }}</div>@endif
        </td>
        <td style="width:50%">
            <h2>Facturé à{{ ! empty($a['organisme']) ? ' (organisme)' : '' }}</h2>
            <div><b>{{ $a['nom'] ?? '' }}</b></div>
            @if (! empty($a['adresse']))<div>{{ $a['adresse'] }}</div>@endif
            @if (! empty($a['telephone']))<div>Tél. {{ $a['telephone'] }}</div>@endif
            @if (! empty($a['email']))<div>{{ $a['email'] }}</div>@endif
        </td>
    </tr>
</table>

<h2>Séjour</h2>
<div>Réservation n° {{ $r['numero'] ?? '' }} — {{ $r['site'] ?? '' }} ({{ $r['ville'] ?? '' }}) — {{ $r['chambre'] ?? '' }}</div>
<div>Du {{ $r['arrivee'] ?? '' }} au {{ $r['depart'] ?? '' }} ({{ $r['nuitees'] ?? '' }} nuitée(s)) — Client : {{ $d['client']['nom'] ?? '' }}</div>
@if ($estAvoir && ! empty($d['motif_avoir']))<div class="avoir">Motif de l'avoir : {{ $d['motif_avoir'] }}</div>@endif

<h2>Détail</h2>
<table class="lignes">
    <tr><th>Désignation</th><th class="r">Qté</th><th class="r">Prix unitaire</th><th class="r">Montant</th></tr>
    @foreach (($d['lignes'] ?? []) as $ligne)
        @php $signe = $estAvoir ? -1 : 1; @endphp
        <tr>
            <td>{{ $ligne['libelle'] }}</td>
            <td class="r">{{ $ligne['quantite'] }}</td>
            <td class="r">{{ $f::nombre($ligne['prix_unitaire']) }}</td>
            <td class="r">{{ $f::nombre($signe * $ligne['montant']) }}</td>
        </tr>
    @endforeach
</table>

<table style="margin-top:10px; width:55%; margin-left:45%">
    <tr><td>Total HT</td><td class="r">{{ $f::fcfa($facture->montant_ht) }}</td></tr>
    <tr><td>TVA ({{ rtrim(rtrim(number_format((float) ($d['tva_taux'] ?? 0), 2, ',', ''), '0'), ',') }} %)</td><td class="r">{{ $f::fcfa($facture->montant_tva) }}</td></tr>
    <tr class="total"><td>Total TTC</td><td class="r">{{ $f::fcfa($facture->montant_ttc) }}</td></tr>
</table>

@if (! $estAvoir)
    <h2>Règlement</h2>
    <div>
        @if (! empty($d['delai_paiement_heures']))
            À régler sous {{ $d['delai_paiement_heures'] }} heures après la remise des clés.
        @else
            Prise en charge par l'organisme : règlement selon les conditions convenues.
        @endif
    </div>
    @if ($propre($e['banque_nom'] ?? null) || $propre($e['banque_iban'] ?? null))
        <div class="petit">Coordonnées bancaires : {{ $propre($e['banque_nom'] ?? null) }} {{ $propre($e['banque_iban'] ?? null) }}</div>
    @endif
@endif
@if ($propre($d['conditions'] ?? null))
    <div class="petit" style="margin-top:14px">{{ $d['conditions'] }}</div>
@endif
</body>
</html>
