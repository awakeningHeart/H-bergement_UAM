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

## [Lot 4 - Gestion et finition] - 2026-11-01
### Ajouté
- Workflow des statuts (`ReservationWorkflow`) : transitions contrôlées, journalisées (auteur, ancien et nouveau statut, motif), SMS de confirmation / refus / expiration / annulation par file d'attente (`EnvoyerSms`).
- Fiche de réservation du back-office : actions Confirmer, Refuser (avec motif), Arrivée / remise des clés, Départ, Non présenté, Annuler, Émettre la facture, Enregistrer un paiement ; consultation en lecture seule pour la Direction.
- Tâches planifiées : `reservations:expirer` (RG09), `reservations:non-presentes` (RG10), en plus de `holds:purger` et `otp:purger`.
- Facturation (`FactureService`) : factures `FAC-AAAA-NNNNN` et avoirs `AV-AAAA-NNNNN`, contenu photographié à l'émission, PDF figé sur disque privé, facture à l'organisme (RG08), échéance à 48 h (RG07) ; avoir total depuis la fiche (RG12).
- Paiements (`PaiementService`) : espèces, virement, chèque, mobile money ; plafonnés au solde ; non modifiables.
- Tableau de bord (indicateurs, statuts, sites, revenus sur 6 mois) limité aux sites de l'utilisateur ; exports Excel des réservations et des paiements (filtres et RG11 respectés).
- En-têtes de sécurité (`EnTetesSecurite`) : nosniff, cadre, Referrer-Policy, Permissions-Policy, HSTS en HTTPS, CSP sur le site public.
- Réservations de démonstration couvrant tous les statuts (jamais en production).
- Guides de déploiement et utilisateur, procès-verbal de recette, checklist complétée.
### Modifié
- Les liens de factures (PDF) sont disponibles dans la page de suivi du client.
- Création manuelle de réservation et édition des paiements retirées du back-office ; réservations non supprimables.

## [Correctifs après revue] - 2026-11-02
### Sécurité
- Un visiteur ne peut plus bloquer toutes les chambres : plafond de 3 blocages simultanés par adresse IP (`HOLDS_MAX_PAR_IP`, empreinte stockée dans `chambre_holds.ip_hash`) ; le formulaire Livewire est aussi limité en débit.
- Photos du back-office : seuls JPEG, PNG et WebP sont acceptés (les SVG, pouvant contenir du script, sont refusés).
### Qualité
- 3 tests ajoutés (171 au total) ; contrôle de non-débordement à 360 px réalisé sur le site public.

## [Logo] - 2026-11-08
### Modifié
- Nouveau logo à fond transparent (`logo-uam-transparent.png`) sur le site public (barre, pied, favicon), le back-office et les PDF (fiche, facture) ; marges du logo ajustées.
### Qualité
- Test du tableau de bord rendu déterministe (date de la réservation de base fixée dans le mois).

## [Interface anglaise (P2)] - 2026-11-08
### Ajouté
- Sélecteur FR / EN dans le bandeau supérieur (langue gardée en session, français par défaut) ; fichiers `lang/en` (site, réservation, statuts et repas, validation).
- Site public, formulaire de réservation, suivi, messages d'erreur et SMS de la visite (code, accusé de réception) en anglais.
- 8 tests (changement de langue, redirection sûre, back-office en français, formulaire en anglais, journal en français).
### Modifié
- Libellés des statuts, motifs et repas lus depuis `lang/*/enums.php` ; le journal d'audit reste en français.
- Messages de validation propres à un champ déplacés dans `lang/*/validation.php` (appliqués aussi au formulaire Livewire).
- Langue par défaut de l'application : `fr` (même sans `APP_LOCALE`).
### Restent en français
- Back-office, fiche PDF, factures, SMS envoyés par le personnel, contenus saisis en base (noms, descriptions, équipements).
