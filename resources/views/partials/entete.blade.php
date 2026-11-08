@php
    $telephone = \App\Models\Parametre::valeur('facturation_telephone');
    $telephone = $telephone && ! str_contains($telephone, 'À renseigner') ? $telephone : '+227 20 31 55 31';
@endphp
<div class="topbar">
    <div class="wrap topbar__inner">
        <span>{{ __('site.topbar') }}</span>
        <div class="topbar__right">
            <span>{{ $telephone }}</span>
            <span class="lang" aria-label="Langue"><b>FR</b> <span>/</span> <span title="{{ __('site.en_bientot') }}">EN</span></span>
            <a href="{{ route('accueil') }}#suivi">{{ __('site.suivre') }}</a>
        </div>
    </div>
</div>

<div class="navbar">
    <nav class="wrap navbar__inner" aria-label="Navigation principale">
        <a class="navbar__logo" href="{{ route('accueil') }}">
            <img src="{{ asset('images/logo-uam-transparent.png') }}" alt="{{ __('site.nom') }}">
        </a>
        <div class="navbar__links">
            <a href="{{ route('accueil') }}#destinations">{{ __('site.nav.destinations') }}</a>
            <a href="{{ route('accueil') }}#chambres">{{ __('site.nav.chambres') }}</a>
            <a href="{{ route('accueil') }}#carte">{{ __('site.nav.carte') }}</a>
            <a href="{{ route('accueil') }}#experience">{{ __('site.nav.experience') }}</a>
            <a class="btn-cta" href="{{ route('accueil') }}#chambres">{{ __('site.nav.reserver') }}</a>
        </div>
    </nav>
</div>
