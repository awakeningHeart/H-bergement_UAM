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

## D05 — Écarts et choix du lot 1
- **Pest 4.7** installé (et non 5.3) : Pest 5 est incompatible avec les contraintes du projet résolues par Composer ; Pest 4 suffit pour les besoins.
- **Fuseau** : `app.timezone = UTC` (stockage UTC) ; saisie/affichage en `Africa/Niamey` via `config('uam.timezone')` et `FilamentTimezone`. Les règles métier sur l'heure (RG05, RG10) devront convertir en heure de Niamey. `APP_TIMEZONE` retiré de `.env.example`.
- **Table `audit_logs`** : modèle `AuditLog` dérivé de l'`Activity` de spatie ; `subject_id` en chaîne (la table `parametres` a une clé texte).
- **Rôles** : permissions fines (`referentiels.*`, `reservations.*`, `paiements.gerer`, `factures.gerer`, `clients.*`, `utilisateurs.gerer`, `parametres.gerer`, `audit.voir`, `exports.generer`). La Direction voit tous les sites en lecture seule ; le gestionnaire est limité aux sites de `site_user` (RG11, scopes `accessiblesPar` + Policies).
- **Réservations** : non supprimables (changement de statut uniquement) ; **factures** : ni modifiables ni supprimables, même par l'admin (RG12). L'onglet « Factures » est en lecture seule ; l'émission viendra au lot 4.
- **Colonne ajoutée** `reservations.idempotence_cle` (unique) pour le double clic à l'envoi (lot 3).
- **Paramètres** : `valeur` saisie en texte dans Filament (la valeur numérique est reconvertie à l'usage).
- **Comptes de démonstration** (`gestionnaire@uam.local`, `direction@uam.local`) : mot de passe aléatoire, à réinitialiser par l'admin. Admin : `ADMIN_EMAIL`/`ADMIN_PASSWORD`, sinon mot de passe généré et affiché une fois par le seeder.
- **Tests** : base MySQL dédiée `uam_hebergement_test` (même moteur que la production, nécessaire pour `lockForUpdate()`).
- **Larastan** : niveau 6 avec les identifiants `missingType.generics` / `missingType.iterableValue` désactivés dans `phpstan.neon` (génériques Eloquent).
- **Traductions** : Filament fournit le français ; les fichiers `lang/fr` applicatifs (validation, auth) restent à ajouter au lot 3.
