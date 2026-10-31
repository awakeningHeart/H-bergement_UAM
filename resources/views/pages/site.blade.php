@extends('layouts.public')

@section('titre', $site->nom)
@section('description', $site->description ?: __('site.hero.texte'))

@section('contenu')
<div class="wrap">
    <nav class="crumbs" aria-label="Fil d'Ariane">
        <a href="{{ route('accueil') }}">{{ __('site.ville.accueil') }}</a> <span>›</span>
        <a href="{{ route('ville', $ville) }}">{{ $ville->nom }}</a> <span>›</span> <span>{{ $site->nom }}</span>
    </nav>
    <div class="page-head">
        <div class="eyebrow" style="margin-top:28px">{{ $ville->nom }}</div>
        <h1 class="page-title">{{ $site->nom }}</h1>
    </div>
</div>

<section class="wrap">
    <div class="fiche">
        <div>
            <div class="gallery__main">
                <x-photo :photo="$site->photos->first()" :alt="$site->nom" :libelle="$site->nom" :lazy="false" />
            </div>
            @if ($site->photos->count() > 1)
                <div class="gallery__thumbs">
                    @foreach ($site->photos->skip(1) as $photo)
                        <img src="{{ $photo->url() }}" alt="" loading="lazy" style="aspect-ratio:4/3;object-fit:cover;width:100%">
                    @endforeach
                </div>
            @endif
        </div>
        <div class="panel">
            @if ($site->description)
                <p class="info-line">{{ $site->description }}</p>
            @endif
            @if ($site->adresse)
                <p class="info-line"><b>{{ __('site.site.adresse') }}</b> : {{ $site->adresse }}</p>
            @endif
            @if ($site->telephone)
                <p class="info-line"><b>{{ __('site.site.telephone') }}</b> : {{ $site->telephone }}</p>
            @endif
            <p class="info-line">{{ __('site.site.chambres_total', ['n' => $nbChambres]) }}</p>
            <x-carte-sites :sites="collect([$site])" :petite="true" />
        </div>
    </div>
</section>

<section id="chambres" class="wrap section">
    <div class="section-head" style="margin-bottom:28px">
        <div>
            <div class="eyebrow">{{ $site->nom }}</div>
            <h2 class="h2">{{ __('site.site.chambres_titre') }}</h2>
        </div>
        <x-filtres-type :types="$types" :type-id="$typeId" />
    </div>
    <div style="margin-bottom:36px">
        <x-recherche :action="route('site', $site)" :arrivee="$arrivee" :depart="$depart" :personnes="$personnes" :avec-ville="false" :plat="true" />
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
