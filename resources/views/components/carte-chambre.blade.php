@props(['chambre', 'arrivee', 'depart'])
@php
    $tarif = $chambre->getAttribute('tarif_en_vigueur');
    $equipements = collect([trans_choice('site.chambres.personnes', $chambre->type->capacite, ['n' => $chambre->type->capacite])])
        ->merge($chambre->type->equipements ?? [])->take(4);
    $lien = route('chambre', ['chambre' => $chambre, 'arrivee' => $arrivee->toDateString(), 'depart' => $depart->toDateString()]);
@endphp
<article class="room">
    <a class="room__photo" href="{{ $lien }}" tabindex="-1" aria-hidden="true">
        <x-photo :photo="$chambre->photos->first()" :alt="$chambre->type->libelle" :libelle="__('site.fiche.photo', ['type' => $chambre->type->libelle])" />
        <span class="badge">{{ $chambre->site->nom }}</span>
    </a>
    <div class="room__body">
        <div class="room__title">
            <h3><a href="{{ $lien }}">{{ $chambre->type->libelle }}</a></h3>
            <span class="room__num">{{ __('site.chambres.numero', ['numero' => $chambre->numero]) }}</span>
        </div>
        <ul class="amen">
            @foreach ($equipements as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
        <div class="room__foot">
            <div>
                <div class="price-label">{{ __('site.chambres.a_partir') }}</div>
                <div class="price">{{ \App\Support\Format::nombre($tarif->prix_nuit) }} <small>{{ __('site.chambres.par_nuit') }}</small></div>
            </div>
            <a class="btn-deep" href="{{ $lien }}" data-reserver="{{ $chambre->id }}" data-arrivee="{{ $arrivee->toDateString() }}" data-depart="{{ $depart->toDateString() }}">{{ __('site.chambres.reserver') }}</a>
        </div>
    </div>
</article>
