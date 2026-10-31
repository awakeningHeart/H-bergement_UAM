@extends('layouts.public')

@php
    $titre = $chambre->type->libelle.' — '.__('site.chambres.numero', ['numero' => $chambre->numero]);
    $equipements = $chambre->type->equipements ?? [];
    $ht = $tarif ? $tarif->prix_nuit * $nuitees : null;
@endphp

@section('titre', $titre.' · '.$site->nom)
@section('description', $chambre->description ?: __('site.hero.texte'))

@section('contenu')
<div class="wrap">
    <nav class="crumbs" aria-label="Fil d'Ariane">
        <a href="{{ route('accueil') }}">{{ __('site.ville.accueil') }}</a> <span>›</span>
        <a href="{{ route('ville', $ville) }}">{{ $ville->nom }}</a> <span>›</span>
        <a href="{{ route('site', $site) }}">{{ $site->nom }}</a> <span>›</span> <span>{{ $titre }}</span>
    </nav>
    <div class="page-head">
        <div class="eyebrow" style="margin-top:28px">{{ $site->nom }}</div>
        <h1 class="page-title">{{ $chambre->type->libelle }} <span style="font-size:.4em;color:var(--gris-2);font-family:'Jost',sans-serif">{{ __('site.chambres.numero', ['numero' => $chambre->numero]) }}</span></h1>
    </div>
</div>

<section class="wrap">
    <div class="fiche">
        <div>
            <div class="gallery__main">
                @if ($chambre->photos->isNotEmpty())
                    <img data-galerie-principale src="{{ $chambre->photos->first()->url() }}" alt="{{ $titre }}">
                @else
                    <x-photo :alt="$titre" :libelle="__('site.fiche.photo', ['type' => $chambre->type->libelle])" />
                @endif
            </div>
            @if ($chambre->photos->count() > 1)
                <div class="gallery__thumbs">
                    @foreach ($chambre->photos as $i => $photo)
                        <button type="button" class="{{ $i === 0 ? 'is-on' : '' }}" data-galerie-vignette="{{ $photo->url() }}" aria-label="Photo {{ $i + 1 }}">
                            <img src="{{ $photo->url() }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif

            <div style="margin-top:36px">
                @if ($chambre->description)
                    <h2 class="block-title">{{ __('site.fiche.description') }}</h2>
                    <p class="info-line">{{ $chambre->description }}</p>
                @endif
                <h2 class="block-title" style="margin-top:28px">{{ __('site.fiche.equipements') }}</h2>
                <ul class="amen">
                    <li>{{ trans_choice('site.chambres.personnes', $chambre->type->capacite, ['n' => $chambre->type->capacite]) }}</li>
                    @foreach ($equipements as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <aside class="panel" id="reservation" aria-label="{{ __('site.fiche.sejour') }}">
            <h2>{{ __('site.fiche.sejour') }}</h2>
            <x-recherche :action="route('chambre', $chambre)" :arrivee="$arrivee" :depart="$depart" :avec-ville="false" :plat="true" ancre="reservation" />

            @if (! $tarif)
                <div class="status status--ko">{{ __('site.fiche.sans_tarif') }}</div>
            @else
                <div class="status {{ $disponible ? '' : 'status--ko' }}" role="status">
                    {{ $disponible ? __('site.fiche.disponible') : __('site.fiche.indisponible') }}
                </div>
                <div>
                    <div class="price-label">{{ __('site.chambres.a_partir') }}</div>
                    <div class="price">{{ \App\Support\Format::nombre($tarif->prix_nuit) }} <small>{{ __('site.chambres.par_nuit') }}</small></div>
                </div>
                @if ($nuitees > 0)
                    <div class="sum">
                        <span>{{ trans_choice('site.fiche.nuitees', $nuitees, ['n' => $nuitees]) }} · {{ __('site.fiche.estimation') }}</span>
                        <strong>{{ \App\Support\Format::fcfa($ht) }}</strong>
                    </div>
                @endif
                <button type="button" class="btn-deep" data-reserver="{{ $chambre->id }}" @disabled(! $disponible || $nuitees < 1)>{{ __('site.chambres.reserver') }}</button>
            @endif
            <a href="{{ route('site', $site) }}">← {{ __('site.fiche.retour') }}</a>
        </aside>
    </div>
</section>

<section class="wrap section">
    <x-carte-sites :sites="collect([$site])" :petite="true" />
</section>
<div style="height:clamp(72px,10vw,140px)"></div>
@endsection
