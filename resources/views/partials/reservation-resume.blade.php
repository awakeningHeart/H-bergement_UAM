@php
    $tz = config('uam.timezone');
    $peutAnnuler = $reservation->statut->peutPasserA(\App\Enums\StatutReservation::Annulee);
@endphp
<article class="resume">
    <header class="resume__tete">
        <div>
            <div class="resume__num">{{ $reservation->numero }}</div>
            <div class="resume__lieu">{{ $reservation->chambre->site->nom }} · {{ $reservation->chambre->type->traduit('libelle') }} — {{ __('site.chambres.numero', ['numero' => $reservation->chambre->numero]) }}</div>
        </div>
        <span class="statut statut--{{ $reservation->statut->couleur() }}">{{ $reservation->statut->libelle() }}</span>
    </header>
    <dl class="resume__liste">
        <div><dt>{{ __('reservation.suivi.sejour') }}</dt>
            <dd>{{ $reservation->arrivee->setTimezone($tz)->format('d/m/Y H:i') }} → {{ $reservation->depart->setTimezone($tz)->format('d/m/Y H:i') }} ({{ trans_choice('site.fiche.nuitees', $reservation->nuitees, ['n' => $reservation->nuitees]) }})</dd></div>
        <div><dt>{{ __('reservation.suivi.montant') }}</dt><dd>{{ \App\Support\Format::fcfa($reservation->montant_ttc) }}</dd></div>
    </dl>
    @if ($reservation->factures->isNotEmpty())
        <div class="resume__actions">
            @foreach ($reservation->factures as $facture)
                <a class="btn-ghost" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('facture.pdf', now()->addMinutes(30), ['numero' => $facture->numero]) }}" target="_blank" rel="noopener">{{ $facture->type->libelle() }} {{ $facture->numero }}</a>
            @endforeach
        </div>
    @endif
    @if (! empty($actions) && $peutAnnuler)
        <footer class="resume__actions">
            <a class="btn-ghost" href="{{ $reservation->getAttribute('lien_fiche') }}" target="_blank" rel="noopener">{{ __('reservation.suivi.fiche_pdf') }}</a>
            <button type="button" class="btn-ghost" wire:click="annuler({{ $reservation->id }})" wire:confirm="{{ __('reservation.suivi.annuler_confirm') }}">{{ __('reservation.suivi.annuler') }}</button>
        </footer>
    @elseif (! empty($actions))
        <footer class="resume__actions">
            <a class="btn-ghost" href="{{ $reservation->getAttribute('lien_fiche') }}" target="_blank" rel="noopener">{{ __('reservation.suivi.fiche_pdf') }}</a>
        </footer>
    @endif
</article>
