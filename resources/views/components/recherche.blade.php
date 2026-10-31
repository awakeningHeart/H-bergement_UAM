@props(['action', 'villes' => collect(), 'ville' => null, 'site' => null, 'arrivee', 'depart', 'personnes' => null, 'plat' => false, 'avecVille' => true, 'ancre' => 'chambres'])
<form method="get" action="{{ $action }}#{{ $ancre }}" data-recherche class="search {{ $plat ? 'search--flat' : '' }}" role="search">
    @if ($avecVille)
        <label>
            <span class="lbl">{{ __('site.recherche.ville') }}</span>
            <select name="ville">
                @foreach ($villes as $v)
                    <option value="{{ $v->slug }}" @selected($ville && $ville->slug === $v->slug)>{{ $v->nom }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="lbl">{{ __('site.recherche.site') }}</span>
            <select name="site">
                <option value="">{{ __('site.recherche.tous_sites') }}</option>
                @foreach ($villes as $v)
                    @foreach ($v->sites as $s)
                        <option value="{{ $s->slug }}" data-ville="{{ $v->slug }}" @selected($site && $site->slug === $s->slug)>{{ $s->nom }}</option>
                    @endforeach
                @endforeach
            </select>
        </label>
    @endif
    <label>
        <span class="lbl">{{ __('site.recherche.arrivee') }}</span>
        <input type="date" name="arrivee" value="{{ $arrivee->toDateString() }}" min="{{ now(config('uam.timezone'))->toDateString() }}" required>
    </label>
    <label>
        <span class="lbl">{{ __('site.recherche.depart') }}</span>
        <input type="date" name="depart" value="{{ $depart->toDateString() }}" min="{{ $arrivee->toDateString() }}" required>
    </label>
    <label>
        <span class="lbl">{{ __('site.recherche.personnes') }}</span>
        <select name="personnes">
            <option value="">—</option>
            @foreach (range(1, 4) as $n)
                <option value="{{ $n }}" @selected($personnes === $n)>{{ trans_choice('site.recherche.personne', $n, ['n' => $n]) }}</option>
            @endforeach
        </select>
    </label>
    <button type="submit" class="search__go">{{ __('site.recherche.bouton') }} <span aria-hidden="true" style="font-size:18px">→</span></button>
</form>
@if ($errors->any())
    <p class="form-error" role="alert">{{ $errors->first() }}</p>
@endif
