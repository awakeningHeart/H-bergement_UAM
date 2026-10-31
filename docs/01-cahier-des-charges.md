# 01 — Cahier des charges

## 1. Contexte
Les maisons d'hôtes de l'UAM (Rectorat, DAIG, Service des Affaires Intérieures) accueillent visiteurs et missionnaires. La réservation se fait aujourd'hui sur fiche papier. La plateforme numérise : recherche par **ville → site → chambre**, demande de réservation en ligne, validation par le gestionnaire, séjour, facturation, suivi.

## 2. Acteurs
| Acteur | Accès | Droits |
|---|---|---|
| Visiteur | public | consulter villes/sites/chambres, tarifs, disponibilités, demander une réservation |
| Client | téléphone + OTP (pas de compte) | suivre/annuler sa demande, télécharger fiche et facture |
| Réceptionniste / gestionnaire de site | back-office (compte) | gérer réservations, check-in/out, paiements, de **ses** sites |
| Administrateur DAIG | back-office | tout : référentiels, tarifs, utilisateurs, paramètres, audit |
| Direction / Rectorat | back-office lecture seule | tableaux de bord, exports |

## 3. Fonctionnalités
### P1 (obligatoire pour le 15/11)
- Catalogue public : villes, sites (carte Leaflet), chambres (photos, équipements, capacité, tarif), recherche par dates avec disponibilités.
- Choix de la chambre puis **formulaire en 3 fenêtres modales** (voir `04-parcours-et-api.md`).
- Vérification AJAX du téléphone (client connu/nouveau) + OTP SMS ; aucun compte client.
- Hold temporaire de chambre, anti double réservation.
- N° de fiche unique ; PDF de la fiche avec QR code ; page de suivi (téléphone + OTP ou lien signé).
- Back-office Filament : villes, sites, types de chambre, chambres, tarifs, tarifs repas, organismes, clients, réservations (workflow de statuts), paiements, factures PDF, utilisateurs/rôles, paramètres, journal d'audit.
- Calcul automatique nuitées / HT / TVA / TTC.
- Check-in / check-out (scan QR possible), no-show, expiration automatique.
- Tableau de bord : taux d'occupation, réservations par statut/site, revenus ; export Excel.
- Notifications SMS (OTP, confirmation, refus) via `SmsGateway`.

### P2
Rappels SMS/WhatsApp, interface anglaise, ménage/maintenance des chambres, 2FA du personnel, réservations de groupe, pièces jointes (ordre de mission).

### P3
Paiement mobile money / carte, application mobile.

## 4. Exigences non fonctionnelles
Responsive mobile first (connexions lentes : pages légères, images optimisées) ; accessibilité de base (labels, focus, contrastes) ; sécurité (OWASP, throttle, CSRF, hash) ; sauvegardes BD quotidiennes ; journal d'audit ; temps de réponse < 2 s sur pages publiques ; français par défaut ; fuseau Africa/Niamey.

## 5. Livrables finaux
Code source versionné, base + seeders de démonstration, guide d'installation/déploiement (Nginx/Apache + Supervisor + cron), guide utilisateur (réceptionniste, administrateur), jeu de tests, PV de recette.
