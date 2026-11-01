# Guide d'installation et de déploiement

Public : service informatique de l'UAM. Cible : serveur Linux (Ubuntu 22.04/24.04), Nginx (ou Apache), PHP 8.4, MySQL 8, Supervisor, cron.

## 1. Prérequis

| Élément | Version / remarque |
|---|---|
| PHP | 8.4 avec extensions `bcmath curl dom fileinfo gd intl mbstring openssl pdo_mysql tokenizer xml zip sodium` |
| Composer | 2.x |
| Node.js | 20+ (compilation des assets uniquement ; inutile en production si `public/build` est livré) |
| MySQL | 8.x, jeu de caractères `utf8mb4` |
| Serveur web | Nginx ou Apache, HTTPS obligatoire |
| Supervisor | pour le worker de file d'attente |

## 2. Installation

```bash
git clone <dépôt> /var/www/uam-hebergement && cd /var/www/uam-hebergement
composer install --no-dev --optimize-autoloader
npm ci && npm run build            # ou copier public/build depuis le poste de développement
cp .env.example .env
php artisan key:generate
```

Créer la base et un utilisateur MySQL dédié (jamais `root`) :

```sql
CREATE DATABASE uam_hebergement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'uam'@'localhost' IDENTIFIED BY '<mot de passe fort>';
GRANT ALL PRIVILEGES ON uam_hebergement.* TO 'uam'@'localhost';
```

## 3. Configuration (`.env`)

Ne jamais versionner `.env`. Valeurs à définir en production :

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hebergement.uam.edu.ne      # domaine réel
APP_LOCALE=fr
UAM_TIMEZONE=Africa/Niamey                  # stockage UTC, affichage heure de Niamey

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=uam_hebergement
DB_USERNAME=uam
DB_PASSWORD=<mot de passe>

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true                  # cookies HTTPS uniquement
SESSION_SAME_SITE=lax

SMS_DRIVER=log                              # log | null | <fournisseur> (voir §7)
SMS_SENDER=UAM
TVA_RATE=0                                  # à confirmer par l'UAM (paramètre modifiable en back-office)
ADMIN_EMAIL=<e-mail de l'administrateur>
ADMIN_PASSWORD=<mot de passe fort>          # utilisé une seule fois par le seeder
```

## 4. Base de données et premier démarrage

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=AdminSeeder --force        # crée l'administrateur initial
php artisan db:seed --class=ParametresSeeder --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- **Ne pas lancer `DatabaseSeeder` en production avec les données de démonstration** : `DemoSeeder` crée des sites et comptes fictifs. En production, saisir les vrais sites, chambres et tarifs dans le back-office (`/admin`).
- Changer le mot de passe de l'administrateur à la première connexion, puis retirer `ADMIN_PASSWORD` du `.env`.

## 5. Serveur web

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name hebergement.uam.edu.ne;
    root /var/www/uam-hebergement/public;
    index index.php;

    client_max_body_size 8M;                 # photos jusqu'à 4 Mo

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

Permissions : `chown -R www-data:www-data storage bootstrap/cache` et `chmod -R ug+rwX storage bootstrap/cache`.

## 6. Tâches planifiées et file d'attente

**Cron** (utilisateur `www-data`) — exécute chaque minute le planificateur Laravel :

```
* * * * * cd /var/www/uam-hebergement && php artisan schedule:run >> /dev/null 2>&1
```

Tâches planifiées :

| Tâche | Fréquence | Rôle |
|---|---|---|
| `holds:purger` | chaque minute | libère les chambres bloquées depuis plus de 15 min (RG13) |
| `reservations:expirer` | 10 min | demandes « En attente » sans réponse depuis 24 h → « Expirée » (RG09) |
| `reservations:non-presentes` | 15 min | arrivée non effectuée le lendemain à 12:00 → « Non présenté » (RG10) |
| `otp:purger` | quotidienne (03:00) | supprime les codes OTP de plus de 24 h |
| `queue:prune-failed` | quotidienne | nettoie les jobs en échec de plus de 7 jours |

**Supervisor** — worker de la file d'attente (envoi des SMS) : `/etc/supervisor/conf.d/uam-worker.conf`

```ini
[program:uam-worker]
command=php /var/www/uam-hebergement/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopasgroup=true
numprocs=1
stdout_logfile=/var/www/uam-hebergement/storage/logs/worker.log
```

Après chaque déploiement : `php artisan queue:restart`.

## 7. Passerelle SMS

L'interface `App\Services\Sms\SmsGateway` a deux implémentations : `LogSmsGateway` (le SMS est écrit dans `storage/logs/laravel.log`) et `NullSmsGateway` (n'envoie rien). **Sans fournisseur branché, aucun client ne reçoit de code ni de confirmation.**

Pour brancher un fournisseur : créer une classe implémentant `SmsGateway` (méthode `envoyer(string $telephone, string $message)`), la déclarer dans `AppServiceProvider::register()` selon `SMS_DRIVER`, et renseigner `SMS_API_KEY` dans le `.env`. Les messages sont courts (moins de 160 caractères) et en français.

## 8. Sauvegardes

- Base : `mysqldump --single-transaction uam_hebergement | gzip > /sauvegardes/uam-$(date +%F).sql.gz` chaque nuit (cron), conservation 30 jours minimum, copie hors serveur.
- Fichiers : sauvegarder `storage/app` (photos publiques dans `storage/app/public`, **factures et avoirs PDF dans `storage/app/private/factures`** — pièces comptables à conserver).
- Tester la restauration au moins une fois avant la mise en production.

## 9. Sécurité

- HTTPS obligatoire (l'en-tête HSTS est envoyé automatiquement sur les requêtes sécurisées).
- En-têtes de sécurité et politique CSP appliqués par l'application (`EnTetesSecurite`). Le front public charge des polices Google Fonts et des tuiles OpenStreetMap : les autoriser dans un éventuel pare-feu applicatif.
- Limitation de débit déjà active : consultation de numéros, envoi et vérification des codes, blocages de chambre, création de réservation, suivi.
- Aucun secret dans le dépôt. Tournez `APP_KEY` uniquement en connaissance de cause (déconnecte tous les utilisateurs).
- Mises à jour de sécurité : `composer audit` et `npm audit` avant chaque livraison.

## 10. Mise à jour

```bash
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

## 11. Vérifications après installation

1. `https://<domaine>/` affiche le site ; `https://<domaine>/admin` la connexion du personnel.
2. `php artisan schedule:list` montre les tâches du §6.
3. `php artisan queue:work --once` ne produit aucune erreur.
4. Faire une réservation de test, vérifier le SMS (journal ou fournisseur), la fiche PDF et le QR code, puis l'annuler.
