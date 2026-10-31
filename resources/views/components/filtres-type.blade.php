@props(['types', 'typeId' => null])
@php $base = request()->except(['type', 'page']); @endphp
<div class="chips" role="group" aria-label="Type de chambre">
    <a class="chip {{ $typeId ? '' : 'is-on' }}" href="{{ request()->url() }}?{{ http_build_query($base) }}#chambres">{{ __('site.recherche.type_toutes') }}</a>
    @foreach ($types as $t)
        <a class="chip {{ $typeId === $t->id ? 'is-on' : '' }}" href="{{ request()->url() }}?{{ http_build_query($base + ['type' => $t->id]) }}#chambres">{{ $t->libelle }}</a>
    @endforeach
</div>
