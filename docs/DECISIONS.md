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

## D06 — Design de référence du handoff (31/10/2026)
- La maquette de référence (hors dépôt) devient la référence visuelle du site public (lots 2-3) et du modal ; voir `docs/05-ui-guidelines.md`. Le back-office Filament garde le thème rouge/noir.
- Écarts entre le design et le cahier des charges, traités par défaut :
  - TVA « 19 % » affichée dans le design = valeur de démonstration ; le taux reste le paramètre `tva` (0 % par défaut tant que l'UAM n'a pas confirmé).
  - Coordonnées du design (tél. +227 20 31 55 31, rectorat@uam.edu.ne, BP 10896/237, NIF 5713) reprises comme valeurs initiales des `parametres`, à faire valider.
  - Villes du design (Niamey, Dosso, Tillabéri, Say) et sites/chambres du design : données de démonstration, à valider.
  - Message de confirmation du design (« envoyée par e-mail ») : la confirmation passe par SMS (e-mail en complément si renseigné).
  - **Motifs de séjour (validé)** : 5 motifs (mission, colloque, jury, enseignant_visiteur, autre) ; colonne `reservations.motif` étendue dans la migration (aucune donnée en production). Le sélecteur « Personnes » filtre les chambres par capacité.
  - **Logo (remplacé le 1er novembre 2026)** : `logo-uam-transparent.png` partout (remplace `logo-hebergement-uam.png`) (site public, back-office, PDF) ; le logo du handoff n'est pas utilisé.

## D07 — Choix du lot 2
- **Disponibilité** : les bornes d'un séjour sont normalisées à l'heure de départ (12:00 à Niamey) ; un départ le matin de J ne bloque donc pas une arrivée l'après-midi de J, mais un départ tardif (> 12:00) bloque le jour J (RG05). Les holds (dates) sont comparés au jour près. Une chambre sans tarif en vigueur n'est pas proposée.
- **Réservation** : les boutons « Réserver cette chambre » mènent à la fiche chambre (attribut `data-reserver`) ; le modal en 3 fenêtres est branché au lot 3. Le formulaire « Retrouver une réservation » est affiché mais désactivé jusqu'au lot 3 (OTP).
- **API** : routes dans `routes/web.php` sous `/api` avec le limiteur `catalogue` (60/min/IP) ; pas de fichier `routes/api.php` (sessions et CSRF pour les POST du lot 3).
- **Visuels d'accueil** : paramètres `image_accueil` et `image_experience` (chemin sur le disque public) ; sans valeur, fond bleu nuit / emplacement neutre. Photos de sites et chambres via le back-office (`php artisan storage:link` requis).
- **Carte** : Leaflet (npm) + tuiles CARTO clair (OpenStreetMap), sans clé API. Le texte public est dans `lang/fr/site.php` (préparation i18n anglais, P2).
- **Non vérifié visuellement** dans un navigateur : le rendu a été contrôlé par tests et réponses HTTP ; une relecture visuelle par le chef est à prévoir.

## D08 — Choix du lot 3
- **OTP uniquement pour un client connu** (docs/04) : un nouveau numéro n'en reçoit pas ; un numéro connu doit valider le code avant toute réservation (preuve en session, 30 min), car la réservation met à jour son identité. Un numéro inconnu reçoit la même réponse sans SMS (pas d'énumération).
- **Heure de départ** : la fenêtre 2 ne demande pas d'heure de départ ; le départ est fixé à l'heure limite (12:00). Un départ tardif relève du gestionnaire. Quantité de chaque repas = nombre de nuitées (comme dans le design).
- **Modal Livewire** : appelle directement les services (même logique que l'API JSON, mêmes limites de débit). Les routes JSON `/api/*` restent exposées et testées.
- **Hold** : un navigateur = un hold ; changer les dates en fenêtre 2 repose le hold. Jeton en clair seulement côté navigateur, empreinte SHA-256 en base.
- **Suivi** : le jeton du lien n'est connu que du SMS et de l'écran de confirmation ; seule son empreinte est stockée. Annulation autorisée pour En attente et Confirmée.
- **Fiche PDF** : lien signé (2 h à la confirmation, 30 min depuis le suivi). La « Facture PDF » du design est reportée au lot 4 (émission des factures).
- **Non encore couvert** : pas de SMS de refus (lot 4, workflow) ; pas d'envoi d'e-mail de confirmation.
- **Tuiles de carte** : OpenStreetMap standard, avec attribution (usage faible volume ; prévoir un fournisseur de tuiles dédié si le trafic augmente).

## D09 — Choix du lot 4
- **Facture émise à la remise des clés** (passage « En cours ») : le délai de paiement de 48 h (RG07) court à partir de là. Émission manuelle possible sur une réservation confirmée, en cours ou terminée. Une facture se corrige par un avoir total, puis une nouvelle facture.
- **Facture photographiée** : colonne `factures.donnees` (JSON) figée à l'émission, PDF écrit sur le disque privé (`storage/app/private/factures`) ; seul `pdf_path` peut changer après création. Les avoirs ont des montants négatifs et une séquence propre (`AV-`).
- **Solde dû** = total des factures (avoirs déduits) ou, sans facture, le TTC tant que la réservation est confirmée, en cours ou terminée ; 0 pour une demande en attente, refusée, expirée, annulée ou non présentée. Les remboursements après avoir ne sont pas gérés.
- **SMS** : confirmation de création, confirmation, refus, expiration et annulation par le personnel partent par la file `database` (`queue:work` requis) ; le code OTP part en direct (latence). L'annulation demandée par le client ne déclenche pas de SMS.
- **Réservations créées uniquement par le parcours public** (pas de création manuelle au guichet) ; le statut, les dates, la chambre et les montants ne sont pas modifiables dans la fiche ; la Direction consulte via une page en lecture seule.
- **Paiements** non modifiables ni supprimables (traçabilité) ; correction = avoir et nouveau paiement.
- **Non présenté** (RG10) : seul le planificateur ou le gestionnaire le déclenche, sans SMS ; le délai se calcule en heure de Niamey (lendemain de l'arrivée à l'heure limite).
- **CSP** : appliquée au site public avec `unsafe-inline` et `unsafe-eval` (nécessaires à Livewire/Alpine) ; non appliquée au back-office Filament ni en environnement local. Les autres en-têtes s'appliquent partout.
- **Tests** : limite mémoire PHP portée à 512 Mo dans `phpunit.xml` (lecture des fichiers Excel).

## D10 — Revue de sécurité (2 novembre 2026)
- **Saturation des chambres** : `POST /api/holds` et le formulaire sont publics. Plafond de 3 holds actifs par adresse IP (empreinte SHA-256 salée par la clé d'application), hors celui que le visiteur remplace. Derrière un proxy inverse, déclarer les proxys de confiance pour que l'IP soit la vraie.
- **Téléversements** : JPEG, PNG, WebP uniquement (pas de SVG servi depuis le domaine de l'application).
- **Constats sans changement** : aucune sortie Blade non échappée (`{!! !!}`), requêtes brutes uniquement statiques, aucune lecture de `env()` hors configuration, annulation de suivi limitée au téléphone vérifié.
