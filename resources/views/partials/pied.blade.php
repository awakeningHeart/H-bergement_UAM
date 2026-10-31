@php
    $param = function (string $cle, string $defaut): string {
        $valeur = \App\Models\Parametre::valeur($cle);

        return is_string($valeur) && $valeur !== '' && ! str_contains($valeur, 'À renseigner') ? $valeur : $defaut;
    };
    $heureDepart = (int) \App\Models\Parametre::valeur('heure_depart', config('uam.checkout_hour'));
    $delaiPaiement = (int) \App\Models\Parametre::valeur('delai_paiement_heures', config('uam.payment_deadline_hours'));
    $email = $param('facturation_email', 'rectorat@uam.edu.ne');
@endphp
<footer class="footer">
    <div class="wrap footer__grid">
        <div class="footer__col">
            <img class="footer__logo" src="{{ asset('images/logo-hebergement-uam.png') }}" alt="{{ __('site.nom') }}" loading="lazy">
        </div>
        <div class="footer__col">
            <b>{{ __('site.pied.service') }}</b>
            <span>Service des Affaires Intérieures : Maisons d'hôtes</span>
            <span>{{ $param('facturation_adresse', 'BP 10896/237 Niamey, Niger') }}</span>
        </div>
        <div class="footer__col">
            <b>{{ __('site.pied.contact') }}</b>
            <span>{{ $param('facturation_telephone', '+227 20 31 55 31') }}</span>
            <a href="mailto:{{ $email }}">{{ $email }}</a>
        </div>
        <div class="footer__col">
            <b>{{ __('site.pied.savoir') }}</b>
            <span>{{ __('site.pied.depart', ['heure' => sprintf('%02dh00', $heureDepart)]) }}</span>
            <span>{{ __('site.pied.paiement', ['heures' => $delaiPaiement]) }}</span>
        </div>
    </div>
    <div class="wrap footer__legal">
        <span>{{ __('site.pied.droits', ['annee' => now()->year]) }}</span>
        <span>NIF {{ $param('facturation_nif', '5713') }}</span>
    </div>
</footer>
