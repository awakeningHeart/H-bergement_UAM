@props(['photo' => null, 'alt' => '', 'libelle' => null, 'lazy' => true])
@if ($photo)
    <img src="{{ $photo->url() }}" alt="{{ $alt }}" @if ($lazy) loading="lazy" @endif>
@else
    <div class="ph" role="img" aria-label="{{ $alt }}">{{ $libelle ?? __('site.photo_a_venir') }}</div>
@endif
