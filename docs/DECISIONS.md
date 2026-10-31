# Décisions techniques

## D01 — Versions retenues (vérifiées sur Packagist le 31/10/2026)
| Paquet | Version stable | Exigence PHP |
|---|---|---|
| laravel/framework | 13.39 | ^8.3 |
| livewire/livewire | 4.4 | ^8.1 |
| filament/filament | 5.11 (requiert Livewire 4) | ^8.2 |
| pestphp/pest | 5.3 | **^8.4** |
| larastan/larastan | 3.13 (Laravel 13 OK) | ^8.2 |
| spatie/laravel-permission | 8.3 | ^8.3 |
| spatie/laravel-activitylog | 5.1 | **^8.4** |
| propaganistas/laravel-phone | 6.1 | ^8.2 |
| barryvdh/laravel-dompdf | 3.1 | ^8.1 |
| maatwebsite/excel | 4.0 | ^8.3 |
| simplesoftwareio/simple-qrcode | 4.2.0 (dernière version, non maintenue depuis 2021) | ^8.0 |

- **PHP 8.4 retenu** (et non 8.3) : requis par Pest 5 et activitylog 5. Sinon repli sur Pest 4 / activitylog 4.
- `simple-qrcode` est abandonné : à valider à l'installation avec Laravel 13 ; sinon remplacer par `bacon/bacon-qr-code` (sa dépendance) directement.

## D02 — Défauts appliqués (ambiguïtés non bloquantes)
- TVA : taux 0 % par défaut (`TVA_RATE=0`), modifiable dans `parametres` — taux réel à confirmer par l'UAM.
- Données institutionnelles (tarifs, noms de sites, coordonnées, banque) : placeholders `[À renseigner]`.
- Hébergement des photos : disque `public` local (`photos` polymorphe).
- Passerelle SMS : `LogSmsGateway` en dev, fournisseur réel branché plus tard.
- Rôles : `admin`, `gestionnaire`, `direction` (lecture seule). Réceptionniste = rôle `gestionnaire`.
- Dépôt git : branche `main`; archives `.zip` et dossier `handoff` exclus via `.gitignore`.

## D03 — Environnement de développement (poste de Mohamed)
- PHP 8.4.27 NTS installé via winget (extensions curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, zip, sodium activées dans php.ini). Composer 2.11 via `composer.phar` (hors admin).
- Laravel 13.39.0 initialisé le 31/10/2026 ; `.env.example` du kit conservé (non écrasé).
- **MySQL 8 non encore installé** : à fournir (conteneur Docker conseillé) avant `migrate` au lot 1. Aucun driver SQLite actif.

## D04 — MySQL local
Conteneur Docker `uam-mysql` (mysql:8.4 LTS, volume `uam-mysql-data`), lié à `127.0.0.1:3306` uniquement, mot de passe root aléatoire stocké dans `.env` (non versionné). utf8mb4, fuseau serveur UTC.
