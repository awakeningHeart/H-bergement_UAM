<div>
@if ($ouvert)
    @php
        $chambre = $this->chambre;
        $devis = $this->devis;
        $messagesEtat = [
            'invalide' => [__('reservation.modal.e1.invalide'), 'bad'],
            'nouveau' => [__('reservation.modal.e1.nouveau'), 'info'],
            'connu' => [__('reservation.modal.e1.connu'), 'info'],
            'verifie' => [__('reservation.modal.e1.verifie'), 'info'],
        ];
    @endphp
    <div class="modal" x-data @keydown.escape.window="$wire.fermer()">
        <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-titre" x-trap.noscroll="true">

            <div class="modal__head">
                <div class="modal__ctx">
                    @if ($chambre)
                        <span>{{ $chambre->site->ville->nom }}</span><span class="dim">·</span>
                        <span>{{ $chambre->site->nom }}</span><span class="dim">·</span>
                        <span class="modal__room" id="modal-titre">{{ $chambre->type->libelle }} — {{ __('site.chambres.numero', ['numero' => $chambre->numero]) }}</span>
                        @if ($devis)
                            <span class="dim">·</span><span class="white">{{ __('reservation.modal.prix', ['prix' => \App\Support\Format::nombre($devis['prix_nuit'])]) }}</span>
                        @endif
                    @else
                        <span class="modal__room" id="modal-titre">{{ __('reservation.modal.titre') }}</span>
                    @endif
                </div>
                <div class="modal__tools">
                    @if ($holdExpire > 0 && $etape >= 1 && $etape <= 3)
                        <span class="modal__timer" wire:key="timer-{{ $holdExpire }}"
                              x-data="{ left: {{ $restant }}, t: null,
                                        init() { this.t = setInterval(() => { if (this.left > 0) { this.left--; } if (this.left <= 0) { clearInterval(this.t); $wire.expirer(); } }, 1000); },
                                        destroy() { clearInterval(this.t); },
                                        fmt() { return Math.floor(this.left / 60) + ':' + String(this.left % 60).padStart(2, '0'); } }">
                            {{ __('reservation.modal.reservee') }} <span x-text="fmt()"></span>
                        </span>
                    @endif
                    <button type="button" class="modal__link" wire:click="fermer">{{ $etape === 4 ? __('reservation.modal.fermer') : __('reservation.modal.changer') }}</button>
                </div>
            </div>

            @if ($etape >= 1 && $etape <= 3)
                <div class="modal__steps">
                    @foreach (__('reservation.modal.etapes') as $i => $libelle)
                        <div class="step {{ $i + 1 <= $etape ? 'is-on' : '' }} {{ $i + 1 === $etape ? 'is-current' : '' }}" @if ($i + 1 === $etape) aria-current="step" @endif>
                            <span class="step__bar"></span>
                            <span class="step__label">0{{ $i + 1 }} · {{ $libelle }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="modal__body">
                @if ($erreur && $etape !== 0)
                    <p class="alert" role="alert">{{ $erreur }}</p>
                @endif

                {{-- Chambre perdue : hold expiré ou chambre prise --}}
                @if ($etape === 0)
                    <h3 class="modal__title">{{ __('reservation.modal.titre') }}</h3>
                    <p class="alert" role="alert">{{ $erreur }}</p>
                    <p><button type="button" class="btn-deep" wire:click="fermer">{{ __('reservation.modal.changer') }}</button></p>
                @endif

                {{-- Fenêtre 1 --}}
                @if ($etape === 1)
                    <h3 class="modal__title">{{ __('reservation.modal.e1.titre') }}</h3>
                    <label class="field field--big">
                        <span class="lbl">{{ __('reservation.modal.e1.telephone') }}</span>
                        <input type="tel" inputmode="tel" autocomplete="tel" name="telephone" wire:model="telephone" wire:blur="verifierTelephone" wire:keydown.enter="verifierTelephone"
                               placeholder="{{ __('reservation.modal.e1.placeholder') }}" aria-describedby="tel-msg" autofocus>
                    </label>
                    <div id="tel-msg" class="hint hint--{{ $messagesEtat[$etatTelephone][1] ?? 'idle' }}" aria-live="polite">
                        {{ $messagesEtat[$etatTelephone][0] ?? '' }}
                        @error('telephone') {{ $message }} @enderror
                    </div>

                    @if ($etatTelephone === 'connu')
                        <div class="otp">
                            <label class="field">
                                <span class="lbl">{{ __('reservation.modal.e1.otp') }}</span>
                                <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" wire:model="code"
                                       wire:keydown.enter="verifierCode" placeholder="• • • • • •" class="otp__input">
                            </label>
                            @if ($messageCode)<p class="hint hint--bad" role="alert">{{ $messageCode }}</p>@endif
                            <div class="otp__actions">
                                <button type="button" class="btn-deep" wire:click="verifierCode">{{ __('reservation.modal.e1.otp_valider') }}</button>
                                <button type="button" class="modal__link modal__link--dark" wire:click="renvoyerCode">{{ __('reservation.modal.e1.otp_renvoyer') }}</button>
                            </div>
                        </div>
                    @elseif ($messageCode)
                        <p class="hint hint--bad" role="alert">{{ $messageCode }}</p>
                    @endif

                    @if (in_array($etatTelephone, ['nouveau', 'verifie'], true))
                        <div class="grid-fields">
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.nom') }}</span>
                                <input type="text" wire:model="nom" autocomplete="family-name">@error('nom')<span class="err">{{ $message }}</span>@enderror</label>
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.prenom') }}</span>
                                <input type="text" wire:model="prenom" autocomplete="given-name">@error('prenom')<span class="err">{{ $message }}</span>@enderror</label>
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.type_piece') }}</span>
                                <select wire:model="type_piece">
                                    <option value=""></option>
                                    {{-- Valeur enregistrée en français (lue par le personnel), libellé dans la langue du visiteur. --}}
                                    @foreach (__('reservation.modal.e1.pieces') as $valeur => $piece)
                                        <option value="{{ $valeur }}">{{ $piece }}</option>
                                    @endforeach
                                </select></label>
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.numero_piece') }}</span>
                                <input type="text" wire:model="numero_piece">@error('numero_piece')<span class="err">{{ $message }}</span>@enderror</label>
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.piece_delivree_le') }}</span>
                                <input type="date" wire:model="piece_delivree_le" max="{{ now(config('uam.timezone'))->toDateString() }}">@error('piece_delivree_le')<span class="err">{{ $message }}</span>@enderror</label>
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e1.email') }}</span>
                                <input type="email" wire:model="email" autocomplete="email">@error('email')<span class="err">{{ $message }}</span>@enderror</label>
                        </div>
                    @endif
                @endif

                {{-- Fenêtre 2 --}}
                @if ($etape === 2)
                    <h3 class="modal__title">{{ __('reservation.modal.e2.titre') }}</h3>
                    <div class="chips" role="group" aria-label="{{ __('reservation.modal.e2.motif') }}" style="margin-bottom:26px">
                        @foreach ($motifs as $valeur => $libelle)
                            <button type="button" class="chip {{ $motif === $valeur ? 'is-on' : '' }}" wire:click="$set('motif', '{{ $valeur }}')" aria-pressed="{{ $motif === $valeur ? 'true' : 'false' }}">{{ $libelle }}</button>
                        @endforeach
                    </div>
                    <div class="grid-fields grid-fields--4">
                        <label class="field"><span class="lbl">{{ __('reservation.modal.e2.arrivee') }}</span>
                            <input type="date" wire:model.live="arrivee" min="{{ now(config('uam.timezone'))->toDateString() }}">@error('arrivee')<span class="err">{{ $message }}</span>@enderror</label>
                        <label class="field"><span class="lbl">{{ __('reservation.modal.e2.heure') }}</span>
                            <input type="time" wire:model.live="heure">@error('heure')<span class="err">{{ $message }}</span>@enderror</label>
                        <label class="field"><span class="lbl">{{ __('reservation.modal.e2.depart', ['heure' => $heureDepart]) }}</span>
                            <input type="date" wire:model.live="depart" min="{{ $arrivee }}">@error('depart')<span class="err">{{ $message }}</span>@enderror</label>
                        <div class="field"><span class="lbl">{{ __('reservation.modal.e2.nuitees') }}</span>
                            <span class="big-number">{{ $nuitees }}</span></div>
                    </div>

                    <div class="box">
                        <div class="box__row">
                            <span class="lbl-dark">{{ __('reservation.modal.e2.repas') }}</span>
                            <div class="seg" role="group">
                                <button type="button" class="{{ $mangeSurPlace ? 'is-on' : '' }}" wire:click="choisirRepas(true)">{{ __('reservation.modal.e2.oui') }}</button>
                                <button type="button" class="{{ ! $mangeSurPlace ? 'is-on' : '' }}" wire:click="choisirRepas(false)">{{ __('reservation.modal.e2.non') }}</button>
                            </div>
                        </div>
                        @if ($mangeSurPlace)
                            <div class="meals">
                                @foreach (\App\Enums\Repas::cases() as $r)
                                    @php $actif = in_array($r->value, $repas, true); @endphp
                                    <button type="button" class="meal {{ $actif ? 'is-on' : '' }}" wire:click="basculerRepas('{{ $r->value }}')" aria-pressed="{{ $actif ? 'true' : 'false' }}">
                                        <span>{{ $actif ? '✓' : '+' }} {{ $r->libelle() }}</span>
                                        <small>{{ __('reservation.modal.e2.par_jour', ['prix' => \App\Support\Format::nombre((int) ($tarifsRepas[$r->value]->prix ?? 0))]) }}</small>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Fenêtre 3 --}}
                @if ($etape === 3)
                    <h3 class="modal__title">{{ __('reservation.modal.e3.titre') }}</h3>
                    <div class="grid-fields">
                        <label class="field"><span class="lbl">{{ __('reservation.modal.e3.correspondant') }}</span>
                            <input type="text" wire:model="correspondant_nom" placeholder="{{ __('reservation.modal.e3.correspondant_ph') }}">@error('correspondant_nom')<span class="err">{{ $message }}</span>@enderror</label>
                        <label class="field"><span class="lbl">{{ __('reservation.modal.e3.correspondant_tel') }}</span>
                            <input type="tel" inputmode="tel" wire:model="correspondant_tel">@error('correspondant_tel')<span class="err">{{ $message }}</span>@enderror</label>
                        <div class="field"><span class="lbl">{{ __('reservation.modal.e3.facture_a') }}</span>
                            <div class="seg seg--full" role="group">
                                <button type="button" class="{{ $payeur === 'client' ? 'is-on' : '' }}" wire:click="$set('payeur', 'client')">{{ __('reservation.modal.e3.moi') }}</button>
                                <button type="button" class="{{ $payeur === 'organisme' ? 'is-on' : '' }}" wire:click="$set('payeur', 'organisme')">{{ __('reservation.modal.e3.organisme') }}</button>
                            </div></div>
                        @if ($payeur === 'organisme')
                            <label class="field"><span class="lbl">{{ __('reservation.modal.e3.organisme_label') }}</span>
                                <select wire:model="organisme_id">
                                    <option value="">{{ __('reservation.modal.e3.choisir') }}</option>
                                    @foreach ($organismes as $o)
                                        <option value="{{ $o->id }}">{{ $o->nom }}</option>
                                    @endforeach
                                </select>@error('organisme_id')<span class="err">{{ $message }}</span>@enderror</label>
                        @endif
                    </div>

                    @if ($devis)
                        <div class="box box--total">
                            @foreach ($devis['lignes'] as $ligne)
                                <div class="sum-row"><span>{{ $ligne['repas']
                                    ? __('reservation.modal.e3.repas_ligne', ['libelle' => $ligne['libelle'], 'n' => $ligne['quantite'], 'prix' => \App\Support\Format::nombre($ligne['prix_unitaire'])])
                                    : __('reservation.modal.e3.nuitees_ligne', ['n' => $ligne['quantite'], 'prix' => \App\Support\Format::nombre($ligne['prix_unitaire'])]) }}</span>
                                    <span>{{ \App\Support\Format::fcfa($ligne['montant']) }}</span></div>
                            @endforeach
                            <div class="sum-row sum-row--sep"><span>{{ __('reservation.modal.e3.total_ht') }}</span><span>{{ \App\Support\Format::fcfa($devis['montant_ht']) }}</span></div>
                            <div class="sum-row"><span>{{ __('reservation.modal.e3.tva', ['taux' => $tauxTva]) }}</span><span>{{ \App\Support\Format::fcfa($devis['montant_tva']) }}</span></div>
                            <div class="sum-final"><span class="lbl-dark">{{ __('reservation.modal.e3.total_ttc') }}</span><span class="big-number">{{ \App\Support\Format::fcfa($devis['montant_ttc']) }}</span></div>
                        </div>
                    @endif

                    <label class="check">
                        <input type="checkbox" wire:model="conditions">
                        <span>{{ __('reservation.modal.e3.conditions', ['heure' => $heureDepart, 'delai' => $delaiPaiement]) }}</span>
                    </label>
                    @error('conditions')<span class="err">{{ $message }}</span>@enderror

                    <label class="field" style="margin-top:22px"><span class="lbl">{{ __('reservation.modal.e3.signature') }}</span>
                        <input type="text" wire:model="signature" class="signature" autocomplete="name">@error('signature')<span class="err">{{ $message }}</span>@enderror</label>
                @endif

                {{-- Confirmation --}}
                @if ($etape === 4)
                    <div class="done">
                        <div>
                            <div class="eyebrow">{{ __('reservation.modal.e4.eyebrow') }}</div>
                            <h3 class="modal__title modal__title--xl">{{ __('reservation.modal.e4.titre', ['prenom' => $prenom]) }}</h3>
                            <p class="lead" style="max-width:none;margin-bottom:24px">{{ __('reservation.modal.e4.texte') }}</p>
                            <div class="lbl">{{ __('reservation.modal.e4.numero') }}</div>
                            <div class="done__num">{{ $numero }}</div>
                            <div class="done__actions">
                                <a class="btn-deep" href="{{ $lienFiche }}" target="_blank" rel="noopener">{{ __('reservation.modal.e4.fiche_pdf') }}</a>
                                @if ($lienSuivi)
                                    <a class="btn-ghost" href="{{ $lienSuivi }}">{{ __('reservation.modal.e4.suivi') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($etape >= 1 && $etape <= 3)
                <div class="modal__foot">
                    <button type="button" class="modal__back" wire:click="retour" style="visibility: {{ $etape > 1 ? 'visible' : 'hidden' }}">{{ __('reservation.modal.retour') }}</button>
                    @if ($etape < 3)
                        <button type="button" class="btn-cta-red" wire:click="continuer" wire:loading.attr="disabled" wire:target="continuer">{{ __('reservation.modal.continuer') }}</button>
                    @else
                        <button type="button" class="btn-cta-red" wire:click="confirmer" wire:loading.attr="disabled" wire:target="confirmer">
                            <span wire:loading.remove wire:target="confirmer">{{ __('reservation.modal.confirmer') }}</span>
                            <span wire:loading wire:target="confirmer">{{ __('reservation.modal.envoi') }}</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
</div>
