@extends('layouts.public')

@section('titre', __('reservation.suivi.titre'))

@section('contenu')
@php
    $lienFiche = \Illuminate\Support\Facades\URL::temporarySignedRoute('fiche.pdf', now()->addMinutes(30), ['numero' => $reservation->numero]);
    $peutAnnuler = $reservation->statut->peutPasserA(\App\Enums\StatutReservation::Annulee);
@endphp
<div class="wrap">
    <div class="page-head" style="padding-top:48px">
        <div class="eyebrow">{{ __('reservation.suivi.titre') }}</div>
        <h1 class="page-title">{{ $reservation->numero }}</h1>
    </div>
</div>
<section class="wrap" style="max-width:820px;margin:0 auto">
    @if (session('statut'))
        <p class="status" role="status">{{ session('statut') }}</p>
    @endif
    @error('annulation')
        <p class="status status--ko" role="alert">{{ $message }}</p>
    @enderror

    @include('partials.reservation-resume', ['reservation' => $reservation])

    <div class="resume__actions" style="margin-top:20px">
        <a class="btn-deep" href="{{ $lienFiche }}" target="_blank" rel="noopener">{{ __('reservation.suivi.fiche_pdf') }}</a>
        @if ($peutAnnuler)
            <form method="post" action="{{ route('suivi.annuler', $jeton) }}" onsubmit="return confirm(@js(__('reservation.suivi.annuler_confirm')))">
                @csrf
                <button type="submit" class="btn-ghost">{{ __('reservation.suivi.annuler') }}</button>
            </form>
        @endif
    </div>
</section>
<div style="height:clamp(72px,10vw,140px)"></div>
@endsection
