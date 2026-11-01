# 07 — Checklist de recette

Légende : ✅ vérifié (test automatisé et/ou essai réel) · 🟡 vérifié en partie (voir le PV) · ⬜ à faire lors de la recette sur le serveur de l'UAM.
Détail et preuves : `docs/PV-RECETTE.md`.

- [x] ✅ Recherche ville → site → chambre fonctionne, indisponibles exclues
- [x] ✅ Téléphone en premier champ ; lookup renvoie seulement `{known}`
- [x] ✅ OTP : expiration 5 min, 3 tentatives, 3 envois/10 min
- [x] ✅ Chambre préremplie en lecture seule ; « Changer de chambre » libère le hold
- [x] ✅ Deux navigateurs sur la même chambre/dates : un seul réussit
- [x] ✅ Nuitées/HT/TVA/TTC corrects (départ après 12:00 = nuitée en plus)
- [x] ✅ N° de fiche séquentiel sans doublon sous concurrence
- [x] ✅ PDF avec QR ; scan ouvre la fiche pour le personnel
- [x] ✅ Suivi client par téléphone+OTP et par lien
- [x] ✅ Workflow statuts + expirations (24 h) + no-show + hold 15 min via scheduler
- [x] ✅ Gestionnaire limité à ses sites ; Direction en lecture seule
- [x] ✅ Facture immuable, avoir fonctionnel
- [x] ✅ Journal d'audit, export Excel, tableau de bord
- [ ] 🟡 Mobile 360 px OK, tests Pest verts, pint et phpstan propres — tests, pint et phpstan ✅ ; contrôle visuel à 360 px ⬜ (à faire sur appareil)
