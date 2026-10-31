# 05 — Guidelines UI

- Couleurs : rouge `#E30613` (actions principales), bleu clair `#1E9BE9` (liens/infos), noir `#141414` (texte), fonds blancs/gris très clair. Police **Public Sans**.
- Logo : `assets/logo-hebergement-uam.png` (version modifiée validée par le chef du service informatique) — utilisé sur le site public (barre de navigation), le back-office, les PDF et factures ; **pas** dans la fiche modale.
- Maquette de référence (hors dépôt) : modal 780 px, barres d'étapes, bandeau chambre verrouillée, pied Retour/Annuler/Continuer/Envoyer ma demande.
- Mobile first : le modal devient plein écran sous 640 px ; champs ≥ 44 px de haut ; autocomplete et `inputmode="tel"` sur le téléphone.
- Accessibilité : `role="dialog"`, focus piégé, labels explicites, erreurs sous les champs, contrastes AA.
- Textes en français ; prix « 25 000 FCFA » (séparateur espace insécable).
- Back-office Filament avec thème rouge/noir.
