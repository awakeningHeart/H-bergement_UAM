# Changelog

## [Lot 1 - Socle] - 2026-10-31
### Ajouté
- Projet Laravel 13.39 / PHP 8.4, MySQL 8.4 (Docker), Filament 5.11, Livewire 4, Pest 4, Larastan niveau 6, Pint.
- Migrations de `docs/03-modele-de-donnees.md` (référentiels, clients, OTP, holds, réservations, repas, factures, paiements, séquences, paramètres, photos, `site_user`, `audit_logs`).
- Modèles, relations, enums (statuts avec transitions, repas, motif, payeur, mode de paiement), factories.
- `Sequence::suivante()` (numérotation avec `lockForUpdate()`), `Parametre::valeur()`, tarif en vigueur (`Chambre::tarifA`), chevauchements RG02 (`Reservation::chevauchant`).
- Facture immuable au niveau modèle (RG12).
- Rôles `admin`, `gestionnaire`, `direction` et permissions (spatie/laravel-permission) ; Policies avec périmètre par site (RG11).
- Journal d'audit (spatie/laravel-activitylog, table `audit_logs`).
- Back-office Filament (`/admin`, thème rouge/noir, logo) : villes, sites, types de chambre, chambres, tarifs, tarifs repas, organismes, clients, réservations, paiements, factures (lecture seule), utilisateurs, paramètres, journal d'audit.
- Seeders : rôles, administrateur, paramètres `[À renseigner]`, 2 villes, 3 sites, 15 chambres, tarifs de démonstration, comptes démo.
- Tests Pest : modèles, règles de gestion, policies, accès back-office (21 tests).

## [Lot 2 - Catalogue et disponibilités] - 2026-10-31
### Ajouté
- Site public reprenant le design du handoff (Cormorant Garamond / Jost, bleu nuit, rouge) : accueil (recherche, villes, chambres disponibles, carte Leaflet, expérience, suivi), pages ville, site (carte, photos) et fiche chambre (galerie, disponibilité, estimation). Responsive, mobile first.
- `AvailabilityService` : disponibilités RG02 (réservations actives + holds non expirés, départ à 12:00 normalisé, filtres site/ville/capacité/type, tarif en vigueur).
- `PricingService` : nuitées (RG05), HT/TVA/TTC (RG06, TVA arrondie au supérieur en entiers), repas, tarif à la date d'arrivée.
- API publique `GET /api/sites?ville=` et `GET /api/chambres/disponibles` (JSON, 60 req/min/IP, sans donnée personnelle).
- Photos des sites et chambres téléversables dans le back-office (ordre modifiable).
- Données de démonstration alignées sur le design (4 villes, 6 sites, 30 chambres, tarifs et repas).
- Tests : chevauchements, holds, statuts, tarification, pages publiques, API, formulaires d'administration (58 tests).

## [Lot 3 - Réservation sans compte] - 2026-10-31
### Ajouté
- Modal Livewire en 3 fenêtres (téléphone et identité, séjour et repas, facturation et confirmation) + écran de confirmation, conforme au design du handoff ; plein écran sous 640 px, focus piégé, Échap pour fermer.
- Lookup du téléphone (`POST /api/clients/lookup` → `{known}` uniquement), OTP SMS (haché, 5 min, 3 tentatives, 3 envois / 10 min, usage unique, même réponse pour un numéro inconnu).
- Passerelle SMS : interface `SmsGateway`, `LogSmsGateway` (dev) et `NullSmsGateway` ; choix par `SMS_DRIVER`.
- Holds de 15 minutes sous verrou (`HoldService`), jeton stocké haché, libération par `holds:purger` (planifié chaque minute) ; « Changer de chambre » libère le hold.
- Estimation (`POST /api/reservations/estimate`) et création transactionnelle (`POST /reservations`) : `lockForUpdate()`, revérification de disponibilité, numéro `UAM-MH-AAAA-NNNNN`, idempotence (double clic), client créé ou mis à jour, jeton de suivi haché, SMS de confirmation.
- Form Requests par fenêtre (`IdentiteRequest`, `SejourRequest`, `FacturationRequest`, `StoreReservationRequest`).
- Fiche PDF avec QR code (lien signé), page de suivi par lien opaque (`/suivi/{jeton}`) avec annulation, suivi par téléphone + code depuis l'accueil, QR → `/admin/scan/{token}` (personnel, RG11).
- Limiteurs de débit : lookup, envoi/vérification OTP, holds, réservations, suivi ; `lang/fr/validation.php`, `lang/fr/reservation.php`.
- Tests (110 au total) : OTP, fuite de données avant OTP, holds, concurrence, création, idempotence, PDF signé, suivi, scan, modal Livewire. Course réelle vérifiée : 6 processus simultanés sur la même chambre → 1 seul hold.
### Modifié
- Tuiles de la carte : OpenStreetMap standard (les tuiles CARTO affichaient « API key required »).
