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
