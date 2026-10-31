@props(['sites', 'petite' => false])
@php
    $donnees = $sites->map(fn ($s) => [
        'nom' => $s->nom,
        'adresse' => $s->adresse,
        'lat' => $s->latitude !== null ? (float) $s->latitude : null,
        'lng' => $s->longitude !== null ? (float) $s->longitude : null,
    ])->values();
@endphp
<div id="carte-sites" class="map {{ $petite ? 'map--small' : '' }}" data-sites="{{ json_encode($donnees, JSON_UNESCAPED_UNICODE) }}" role="region" aria-label="{{ __('site.carte.eyebrow') }}"></div>
