<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', __('site.nom')) — {{ __('site.nom') }}</title>
    <meta name="description" content="@yield('description', __('site.hero.texte'))">
    <link rel="icon" href="{{ asset('images/logo-hebergement-uam.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a href="#contenu" class="sr-only-focusable">{{ __('site.skip') }}</a>
    @include('partials.entete')
    <main id="contenu">
        @yield('contenu')
    </main>
    @include('partials.pied')
    <livewire:reservation-modal />
    @livewireScripts
</body>
</html>
