# 05 — Guidelines UI

- Couleurs : rouge `#E30613` (actions principales), bleu clair `#1E9BE9` (liens/infos), noir `#141414` (texte), fonds blancs/gris très clair. Police **Public Sans**.
- Logo : `assets/logo-hebergement-uam.png` (version modifiée validée par le chef du service informatique) — utilisé sur le site public (barre de navigation), le back-office, les PDF et factures ; **pas** dans la fiche modale.
- Maquette de référence (hors dépôt) : modal 780 px, barres d'étapes, bandeau chambre verrouillée, pied Retour/Annuler/Continuer/Envoyer ma demande.
- Mobile first : le modal devient plein écran sous 640 px ; champs ≥ 44 px de haut ; autocomplete et `inputmode="tel"` sur le téléphone.
- Accessibilité : `role="dialog"`, focus piégé, labels explicites, erreurs sous les champs, contrastes AA.
- Textes en français ; prix « 25 000 FCFA » (séparateur espace insécable).
- Back-office Filament avec thème rouge/noir.

## Design de référence — prioritaire
Le design à reproduire pixel-perfect est la maquette de référence fournie (hors dépôt). Il remplace, pour le site public et le modal de réservation, la première maquette et les couleurs/police ci-dessus :
- Polices : **Cormorant Garamond** (titres, prix) + **Jost** (texte). Fond `#F6F2EA`, bleu nuit `--deep #0A1B3D`, rouge `#E30613` (CTA), bleu `#0B4EA2` (liens/puces), texte `#121722`, gris `#4D5566` / `#6B7383`, filets `#E2D9C8`.
- Structure de la page : bandeau supérieur (contact, FR/EN, « Suivre ma réservation »), barre sticky avec logo `public/images/logo-uam-transparent.png`, hero plein écran avec barre de recherche (ville, arrivée, départ, personnes), sections 01 Destinations (cartes villes), 02 Chambres & suites (filtres + cartes), 03 Carte des sites (Leaflet, fond CARTO clair), 04 L'expérience, « Retrouver une réservation », pied de page.
- Modal 780 px : bandeau bleu nuit (ville · site · chambre · prix, compte à rebours du hold, « Changer de chambre »), 3 étapes (Téléphone et identité / Séjour et repas / Facturation et confirmation), écran de confirmation avec QR.
