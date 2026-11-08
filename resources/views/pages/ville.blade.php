@extends('layouts.public')

@section('titre', $ville->nom)

@section('contenu')
<div class="wrap">
    <nav class="crumbs" aria-label="{{ __('site.fil_ariane') }}">
        <a href="{{ route('accueil') }}">{{ __('site.ville.accueil') }}</a> <span>›</span> <span>{{ $ville->nom }}</span>
    </nav>
    <div class="page-head">
        <div class="eyebrow" style="margin-top:28px">{{ __('site.baseline') }}</div>
        <h1 class="page-title">{{ $ville->nom }}</h1>
    </div>
</div>

<section class="wrap" id="sites">
    <h2 class="block-title">{{ __('site.ville.sites_titre', ['ville' => $ville->nom]) }}</h2>
    <div class="site-grid">
        @foreach ($sites as $s)
            <a class="site-card" href="{{ route('site', ['site' => $s, 'arrivee' => $arrivee->toDateString(), 'depart' => $depart->toDateString()]) }}">
                <div class="room__photo">
                    <x-photo :photo="$s->photos->first()" :alt="$s->nom" :libelle="$s->nom" />
                </div>
                <div class="room__body">
                    <h3>{{ $s->nom }}</h3>
                    <p>{{ $s->traduit('adresse') }}</p>
                    <p>{{ __('site.carte.chambres', ['n' => $s->chambres_count]) }}</p>
                </div>
            </a>
        @endforeach
    </div>
</section>

<section class="wrap section">
    <div class="map-block">
        <div class="map-block__text">
            <div class="eyebrow">{{ __('site.carte.eyebrow') }}</div>
            <div class="map-list">
                @foreach ($sites->values() as $i => $s)
                    <button type="button" class="map-item" data-carte-site="{{ $i }}">
                        <span class="map-item__dot"></span>
                        <span>
                            <span class="map-item__name">{{ $s->nom }}</span>
                            <span class="map-item__addr">{{ $s->traduit('adresse') }}</span>
                        </span>
                        <span class="map-item__rooms">{{ __('site.carte.chambres', ['n' => $s->chambres_count]) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <x-carte-sites :sites="$sites" />
    </div>
</section>

<section id="chambres" class="wrap section">
    <div class="section-head" style="margin-bottom:28px">
        <div>
            <div class="eyebrow">{{ __('site.chambres.eyebrow', ['ville' => $ville->nom]) }}</div>
            <h2 class="h2">{{ __('site.chambres.titre') }}</h2>
        </div>
        <x-filtres-type :types="$types" :type-id="$typeId" />
    </div>
    <div style="margin-bottom:36px">
        <x-recherche :action="route('ville', $ville)" :arrivee="$arrivee" :depart="$depart" :personnes="$personnes" :avec-ville="false" :plat="true" />
    </div>

    @if ($chambres->isEmpty())
        <p class="empty">{{ __('site.chambres.vide') }}</p>
    @else
        <div class="room-grid">
            @foreach ($chambres as $chambre)
                <x-carte-chambre :chambre="$chambre" :arrivee="$arrivee" :depart="$depart" />
            @endforeach
        </div>
    @endif
</section>
@endsection
