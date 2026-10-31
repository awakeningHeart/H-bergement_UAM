<div class="suivi__zone">
    @if ($etape === 'telephone')
        <form class="suivi__form" wire:submit="envoyerCode">
            <input type="tel" inputmode="tel" autocomplete="tel" wire:model="telephone" placeholder="{{ __('site.suivi.placeholder') }}" aria-label="{{ __('reservation.suivi.telephone') }}" required>
            <button type="submit" class="btn-deep">{{ __('site.suivi.bouton') }}</button>
        </form>
    @elseif ($etape === 'code')
        <form class="suivi__form" wire:submit="verifier">
            <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" wire:model="code" placeholder="• • • • • •" aria-label="{{ __('reservation.modal.e1.otp') }}" required>
            <button type="submit" class="btn-deep">{{ __('reservation.suivi.valider') }}</button>
        </form>
    @endif

    @if ($message)
        <p class="suivi__msg" role="status">{{ $message }}</p>
    @endif

    @if ($etape === 'liste')
        <div class="suivi__liste">
            <div class="suivi__liste-tete">
                <h3>{{ __('reservation.suivi.vos_reservations') }}</h3>
                <button type="button" class="modal__link modal__link--dark" wire:click="quitter">{{ __('reservation.suivi.deconnexion') }}</button>
            </div>
            @forelse ($liste as $r)
                @include('partials.reservation-resume', ['reservation' => $r, 'actions' => true])
            @empty
                <p class="empty">{{ __('reservation.suivi.aucune') }}</p>
            @endforelse
        </div>
    @endif
</div>
