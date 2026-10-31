@extends('layouts.public')

@php
    $image = function (string $cle): ?string {
        $chemin = \App\Models\Parametre::valeur($cle);

        return is_string($chemin) && $chemin !== '' && ! str_contains($chemin, 'À renseigner') ? asset('storage/'.ltrim($chemin, '/')) : null;
    };
    $imageHero = $image('image_accueil');
    $imageExperience = $image('image_experience');
@endphp

@section('titre', __('site.baseline'))

@section('contenu')
<header class="hero">
    <div class="hero__bg">
        @if ($imageHero)
            <img src="{{ $imageHero }}" alt="">
        @endif
    </div>
    <div class="hero__shade"></div>

    <div class="wrap hero__body">
        <div class="hero__eyebrow">{{ __('site.hero.eyebrow') }}</div>
        <h1>{{ __('site.hero.titre_1') }} <em>{{ __('site.hero.titre_2') }}</em> {{ __('site.hero.titre_3') }}</h1>
        <p>{{ __('site.hero.texte') }}</p>
    </div>

    <div class="wrap hero__search">
        <x-recherche :action="route('accueil')" :villes="$villes" :ville="$ville" :site="$site" :arrivee="$arrivee" :depart="$depart" :personnes="$personnes" />
    </div>
</header>

<section id="destinations" class="wrap section">
    <div class="section-head">
        <div>
            <div class="eyebrow">{{ __('site.destinations.eyebrow') }}</div>
            <h2 class="h2" style="max-width:16ch">{{ __('site.destinations.titre') }} <em>{{ __('site.destinations.titre_em') }}</em></h2>
        </div>
        <p class="lead">{{ __('site.destinations.texte') }}</p>
    </div>
    <div class="city-grid">
        @foreach ($villes as $v)
            @php $photo = $v->sites->flatMap->photos->first(); @endphp
            <a class="city-card" href="{{ route('ville', $v) }}">
                @if ($photo)
                    <img src="{{ $photo->url() }}" alt="" loading="lazy">
                @else
                    <div class="city-card__bg"></div>
                @endif
                <div class="city-card__shade"></div>
                <div class="city-card__text">
                    <div>
                        <div class="city-card__region">{{ __('site.baseline') }}</div>
                        <div class="city-card__name">{{ $v->nom }}</div>
                    </div>
                    <span class="city-card__count">{{ trans_choice('site.destinations.sites', $v->sites->count(), ['n' => $v->sites->count()]) }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>

<section id="chambres" class="wrap section">
    <div class="section-head" style="margin-bottom:40px">
        <div>
            <div class="eyebrow">{{ __('site.chambres.eyebrow', ['ville' => $ville?->nom]) }}</div>
            <h2 class="h2">{{ __('site.chambres.titre') }}</h2>
            <p class="info-line" style="margin-top:14px">{{ __('site.chambres.du_au', ['arrivee' => $arrivee->format('d/m/Y'), 'depart' => $depart->format('d/m/Y')]) }}@if ($site) · {{ $site->nom }}@endif</p>
        </div>
        <x-filtres-type :types="$types" :type-id="$typeId" />
    </div>

    @if ($chambres->isEmpty())
        <p class="empty">{{ __('site.chambres.vide') }}</p>
    @else
        <div class="room-grid">
            @foreach ($chambres as $chambre)
                <x-carte-chambre :chambre="$chambre" :arrivee="$arrivee" :depart="$depart" />
            @endforeach
        </div>
        @if ($totalChambres > $chambres->count() && $ville)
            <p class="more">
                <a class="btn-ghost" href="{{ route('ville', ['ville' => $ville, 'arrivee' => $arrivee->toDateString(), 'depart' => $depart->toDateString()]) }}">{{ __('site.chambres.voir_tout', ['n' => $totalChambres]) }}</a>
            </p>
        @endif
    @endif
</section>

<section id="carte" class="wrap section">
    <div class="map-block">
        <div class="map-block__text">
            <div class="eyebrow">{{ __('site.carte.eyebrow') }}</div>
            <h2>{{ __('site.carte.titre') }} <em>{{ __('site.carte.titre_em') }}</em> {{ __('site.carte.titre_fin') }}</h2>
            <div class="map-list">
                @foreach ($sites->values() as $i => $s)
                    <button type="button" class="map-item" data-carte-site="{{ $i }}">
                        <span class="map-item__dot"></span>
                        <span>
                            <span class="map-item__name">{{ $s->nom }}</span>
                            <span class="map-item__addr">{{ $s->adresse }}</span>
                        </span>
                        <span class="map-item__rooms">{{ __('site.carte.chambres', ['n' => $s->chambres_count]) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <x-carte-sites :sites="$sites" />
    </div>
</section>

<section id="experience" class="wrap section">
    <div class="experience">
        <div class="experience__photo">
            @if ($imageExperience)
                <img src="{{ $imageExperience }}" alt="" loading="lazy">
            @else
                <div class="ph" role="img" aria-label="">{{ __('site.photo_a_venir') }}</div>
            @endif
        </div>
        <div>
            <div class="eyebrow">{{ __('site.experience.eyebrow') }}</div>
            <h2 class="h2" style="margin-bottom:48px">{{ __('site.experience.titre') }} <em>{{ __('site.experience.titre_em') }}</em></h2>
            @foreach (__('site.experience.promesses') as $p)
                <div class="promise">
                    <span class="promise__n">{{ $p['n'] }}</span>
                    <div>
                        <h3>{{ $p['t'] }}</h3>
                        <p>{{ $p['d'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="suivi" class="suivi">
    <div class="wrap suivi__inner">
        <div>
            <h2>{{ __('site.suivi.titre') }}</h2>
            <p>{{ __('site.suivi.texte') }}</p>
        </div>
        <livewire:suivi-telephone />
    </div>
</section>
@endsection
