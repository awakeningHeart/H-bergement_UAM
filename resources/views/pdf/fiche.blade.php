<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('reservation.pdf.titre') }} {{ $reservation->numero }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #121722; }
        h1 { font-size: 18px; margin: 0; color: #0a1b3d; }
        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #0b4ea2; margin: 18px 0 6px; border-bottom: 1px solid #e2d9c8; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        td.l { width: 38%; color: #6b7383; }
        .entete td { vertical-align: middle; }
        .num { font-size: 15px; font-weight: bold; color: #e30613; }
        .montants td { padding: 4px 0; border-bottom: 1px solid #ece5d8; }
        .montants td.r { text-align: right; }
        .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #0a1b3d; border-bottom: 0; }
        .petit { font-size: 9px; color: #4d5566; line-height: 1.5; }
    </style>
</head>
<body>
@php
    $tz = config('uam.timezone');
    $client = $reservation->client;
    $chambre = $reservation->chambre;
@endphp
<table class="entete">
    <tr>
        <td style="width:45%"><img src="{{ $logo }}" alt="Hébergement UAM" style="height:56px"></td>
        <td style="text-align:right">
            <h1>{{ __('reservation.pdf.titre') }}</h1>
            <div class="num">{{ $reservation->numero }}</div>
            <div class="petit">{{ __('reservation.pdf.statut') }} : {{ $reservation->statut->libelle() }}</div>
        </td>
    </tr>
</table>

<h2>{{ __('reservation.pdf.client') }}</h2>
<table>
    <tr><td class="l">Nom</td><td>{{ $client->prenom }} {{ $client->nom }}</td></tr>
    <tr><td class="l">{{ __('reservation.pdf.telephone') }}</td><td>{{ $client->telephone }}</td></tr>
    @if ($client->email)<tr><td class="l">E-mail</td><td>{{ $client->email }}</td></tr>@endif
    @if ($client->numero_piece)<tr><td class="l">{{ $client->type_piece ?: 'Pièce' }}</td><td>{{ $client->numero_piece }}@if ($client->piece_delivree_le) — {{ $client->piece_delivree_le->format('d/m/Y') }}@endif</td></tr>@endif
</table>

<h2>{{ __('reservation.pdf.chambre') }}</h2>
<table>
    <tr><td class="l">Site</td><td>{{ $chambre->site->nom }} — {{ $chambre->site->ville->nom }}</td></tr>
    <tr><td class="l">{{ __('reservation.pdf.chambre') }}</td><td>{{ $chambre->type->libelle }} — n° {{ $chambre->numero }}</td></tr>
</table>

<h2>{{ __('reservation.pdf.sejour') }}</h2>
<table>
    <tr><td class="l">{{ __('reservation.pdf.motif') }}</td><td>{{ $reservation->motif->libelle() }}</td></tr>
    <tr><td class="l">{{ __('reservation.pdf.arrivee') }}</td><td>{{ $reservation->arrivee->setTimezone($tz)->format('d/m/Y à H:i') }}</td></tr>
    <tr><td class="l">{{ __('reservation.pdf.depart') }}</td><td>{{ $reservation->depart->setTimezone($tz)->format('d/m/Y à H:i') }}</td></tr>
    <tr><td class="l">{{ __('reservation.pdf.nuitees') }}</td><td>{{ $reservation->nuitees }}</td></tr>
    @if ($reservation->correspondant_nom)<tr><td class="l">{{ __('reservation.pdf.correspondant') }}</td><td>{{ $reservation->correspondant_nom }}@if ($reservation->correspondant_tel) — {{ $reservation->correspondant_tel }}@endif</td></tr>@endif
    <tr><td class="l">{{ __('reservation.pdf.payeur') }}</td><td>{{ $reservation->payeur->libelle() }}@if ($reservation->organisme) — {{ $reservation->organisme->nom }}@endif</td></tr>
</table>

<h2>{{ __('reservation.pdf.montants') }}</h2>
<table class="montants">
    <tr><td>Nuitées ({{ $reservation->nuitees }})</td><td class="r">{{ \App\Support\Format::fcfa($reservation->montant_ht - $reservation->repas->sum(fn ($l) => $l->quantite * $l->prix_unitaire)) }}</td></tr>
    @foreach ($reservation->repas as $ligne)
        <tr><td>{{ $ligne->repas->libelle() }} · {{ $ligne->quantite }} × {{ \App\Support\Format::nombre($ligne->prix_unitaire) }}</td><td class="r">{{ \App\Support\Format::fcfa($ligne->quantite * $ligne->prix_unitaire) }}</td></tr>
    @endforeach
    <tr><td>Total HT</td><td class="r">{{ \App\Support\Format::fcfa($reservation->montant_ht) }}</td></tr>
    <tr><td>TVA</td><td class="r">{{ \App\Support\Format::fcfa($reservation->montant_tva) }}</td></tr>
    <tr class="total"><td>Total TTC</td><td class="r">{{ \App\Support\Format::fcfa($reservation->montant_ttc) }}</td></tr>
</table>

<table style="margin-top:18px">
    <tr>
        <td style="width:62%">
            <h2 style="margin-top:0">{{ __('reservation.pdf.conditions') }}</h2>
            <div class="petit">{{ $conditions }}</div>
            <div class="petit" style="margin-top:8px">{{ __('reservation.pdf.signature') }} : <i>{{ $reservation->signature }}</i>
                @if ($reservation->conditions_acceptees_le)({{ $reservation->conditions_acceptees_le->setTimezone($tz)->format('d/m/Y H:i') }})@endif</div>
        </td>
        <td style="text-align:center">
            <img src="{{ $qr }}" alt="QR" style="width:130px;height:130px">
            <div class="petit">{{ __('reservation.pdf.qr') }}</div>
        </td>
    </tr>
</table>
</body>
</html>
