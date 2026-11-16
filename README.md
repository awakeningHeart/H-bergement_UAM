# Hébergement UAM — Plateforme de réservation

Plateforme web de réservation des maisons d'hôtes de l'**Université Abdou Moumouni de Niamey (UAM)** : catalogue public des villes, sites et chambres, réservation en ligne sans compte client, et back-office de gestion pour le personnel.

## Fonctionnalités

- **Site public** (français / anglais) : villes, sites sur carte (Leaflet + OpenStreetMap), chambres avec photos, équipements et tarifs, recherche par dates avec disponibilités.
- **Réservation sans compte** en 3 étapes : identification par téléphone + code SMS, chambre bloquée 15 minutes pendant la saisie, protection contre la double réservation, numéro de fiche `UAM-MH-AAAA-NNNNN`.
- **Fiche PDF avec QR code** et page de suivi (lien reçu par SMS, ou téléphone + code) avec annulation.
- **Back-office** (Filament) : référentiels, workflow des statuts (confirmation, refus, arrivée, départ, non-présentation), factures et avoirs, paiements, tableau de bord, exports Excel, journal d'audit. Chaque gestionnaire ne voit que ses sites.
- **Tâches planifiées** : expiration des demandes, non-présentations, libération des blocages et des codes.

## Stack

PHP 8.3+, Laravel 13, MySQL 8, Filament 5 / Livewire 4, Alpine.js, Tailwind CSS, Vite. Tests : Pest ; qualité : Larastan (niveau 6) et Laravel Pint.

## Installation (développement)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# renseigner la base MySQL dans .env, puis :
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Site public : http://127.0.0.1:8000 — back-office : http://127.0.0.1:8000/admin (compte défini par `ADMIN_EMAIL` / `ADMIN_PASSWORD` dans `.env`).

En développement, les SMS sont écrits dans `storage/logs/laravel.log` (`SMS_DRIVER=log`).

## Commandes utiles

```bash
php artisan test              # tests
./vendor/bin/pint             # style de code
./vendor/bin/phpstan analyse  # analyse statique
php artisan queue:work        # envoi des SMS en file d'attente
php artisan schedule:work     # tâches planifiées (en production : cron)
```

## Documentation

- `docs/01-cahier-des-charges.md` à `docs/07-checklist-recette.md` : périmètre, règles de gestion, modèle de données, parcours et API, interface, planning, recette.
- `docs/DECISIONS.md` : choix techniques et fonctionnels.
- `docs/GUIDE-DEPLOIEMENT.md` et `docs/GUIDE-UTILISATEUR.md`.
- `docs/PV-RECETTE.md` : procès-verbal de recette.
- `docs/DEMO-EN-LIGNE.md` : mise en ligne de démonstration gratuite (Render + Aiven).
- `CHANGELOG.md` : historique des versions.
