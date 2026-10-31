import L from 'leaflet';

/**
 * Carte Leaflet des sites (OpenStreetMap via CARTO, sans clé API).
 * Les sites sont fournis par l'attribut data-sites de l'élément #carte-sites.
 */
function initCarte() {
    const el = document.getElementById('carte-sites');
    if (!el) return;

    const sites = JSON.parse(el.dataset.sites || '[]').filter((s) => s.lat !== null && s.lng !== null);
    const map = L.map(el, { scrollWheelZoom: false }).setView(sites.length ? [sites[0].lat, sites[0].lng] : [13.511, 2.1], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap © CARTO',
        maxZoom: 19,
    }).addTo(map);

    const icon = L.divIcon({
        className: '',
        html: '<div style="width:18px;height:18px;border-radius:50%;background:#E30613;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.35)"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });

    const markers = sites.map((s) => {
        const popup = document.createElement('div');
        const titre = document.createElement('b');
        titre.style.cssText = 'font-family:Cormorant Garamond,serif;font-size:17px';
        titre.textContent = s.nom;
        popup.append(titre, document.createElement('br'), document.createTextNode(s.adresse || ''));
        return L.marker([s.lat, s.lng], { icon }).addTo(map).bindPopup(popup);
    });

    if (sites.length > 1) {
        map.fitBounds(L.latLngBounds(sites.map((s) => [s.lat, s.lng])), { padding: [40, 40], maxZoom: 14 });
    }

    document.querySelectorAll('[data-carte-site]').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            const i = Number(bouton.dataset.carteSite);
            const site = sites[i];
            if (!site) return;
            map.flyTo([site.lat, site.lng], 15);
            markers[i].openPopup();
        });
    });
}

/** Limite la liste des sites à la ville choisie dans le formulaire de recherche. */
function initRecherche() {
    document.querySelectorAll('form[data-recherche]').forEach((form) => {
        const ville = form.querySelector('select[name="ville"]');
        const site = form.querySelector('select[name="site"]');

        if (ville && site) {
            const filtrer = () => {
                let selectionOk = false;
                site.querySelectorAll('option[data-ville]').forEach((option) => {
                    const visible = !ville.value || option.dataset.ville === ville.value;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible && option.selected) selectionOk = true;
                });
                if (!selectionOk) site.value = '';
            };
            ville.addEventListener('change', filtrer);
            filtrer();
        }

        // La date de départ suit la date d'arrivée.
        const arrivee = form.querySelector('input[name="arrivee"]');
        const depart = form.querySelector('input[name="depart"]');
        if (arrivee && depart) {
            arrivee.addEventListener('change', () => {
                if (!arrivee.value) return;
                depart.min = arrivee.value;
                if (!depart.value || depart.value <= arrivee.value) {
                    const d = new Date(arrivee.value + 'T00:00:00');
                    d.setDate(d.getDate() + 1);
                    depart.value = d.toISOString().slice(0, 10);
                }
            });
        }
    });
}

/** Galerie de la fiche chambre : la miniature choisie devient l'image principale. */
function initGalerie() {
    const principale = document.querySelector('[data-galerie-principale]');
    if (!principale) return;
    document.querySelectorAll('[data-galerie-vignette]').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            principale.src = bouton.dataset.galerieVignette;
            document.querySelectorAll('[data-galerie-vignette]').forEach((b) => b.classList.remove('is-on'));
            bouton.classList.add('is-on');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCarte();
    initRecherche();
    initGalerie();
});
