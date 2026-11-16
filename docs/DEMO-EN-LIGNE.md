# Démonstration en ligne (gratuite)

Mise en ligne de démonstration pour quelques testeurs : **Render** (site) + **Aiven** (base MySQL), offres gratuites, sans carte bancaire. Ce n'est pas l'hébergement de production (voir `GUIDE-DEPLOIEMENT.md`).

## Limites à connaître

- Le site **s'endort après 15 minutes sans visite** : le premier chargement suivant prend environ une minute.
- **Aucun SMS n'est envoyé** (`SMS_DRIVER=log`). Les codes de vérification apparaissent dans les journaux Render (onglet *Logs*, chercher « code »). Les testeurs peuvent réserver avec un numéro jamais utilisé : aucun code n'est alors demandé.
- Les **photos ajoutées** dans le back-office sont perdues à chaque redémarrage du site (disque non persistant). Les données (réservations, clients…) sont conservées dans la base.
- L'offre MySQL gratuite d'Aiven est annoncée comme **arrêtée le 21 décembre 2026**.
- Données de démonstration uniquement (tarifs, sites, réservations fictives).

## 1. Base de données (Aiven)

1. Créer un compte sur https://aiven.io, puis **Create service** → **MySQL** → offre **Free**.
2. Une fois le service démarré (quelques minutes), ouvrir l'onglet **Overview** et noter : *Host*, *Port*, *User* (`avnadmin`), *Password*, *Database name* (`defaultdb`).
3. Télécharger le **CA certificate** (même page) et ouvrir le fichier `ca.pem` avec le Bloc-notes : son contenu servira à l'étape 3.

## 2. Clé de l'application

Sur le poste de développement, dans le dossier du projet :

```
php artisan key:generate --show
```

Copier la valeur affichée (`base64:…`). Ne pas la publier.

## 3. Site (Render)

1. Créer un compte sur https://render.com avec le compte GitHub du dépôt.
2. **New** → **Blueprint** → choisir le dépôt `H-bergement_UAM`. Render lit `render.yaml` et propose le service `hebergement-uam` (offre *Free*).
3. Renseigner les valeurs demandées :

| Variable | Valeur |
|---|---|
| `APP_KEY` | la clé de l'étape 2 |
| `APP_URL` | `https://hebergement-uam.onrender.com` (à corriger si Render attribue une autre adresse) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | les valeurs Aiven de l'étape 1 |
| `DB_CA_CERT` | tout le contenu du fichier `ca.pem` (lignes `BEGIN` et `END` comprises) |
| `ADMIN_EMAIL` | l'adresse de connexion au back-office |
| `ADMIN_PASSWORD` | un mot de passe solide (12 caractères ou plus) |

   Dans **Environment**, chaque variable a deux champs : **Key** = le nom (ex. `APP_KEY`), **Value** = la valeur complète (ex. `base64:…`). Si le service a été créé sans *Blueprint* (**New** → **Web Service**), ajouter à la main ces dix variables : les autres réglages sont fournis par l'image.
4. Valider. La première construction prend 5 à 10 minutes. Si `APP_KEY` est absente ou mal recopiée, le déploiement échoue avec un message « ERREUR : APP_KEY… » dans les journaux. Au démarrage, le site crée les tables et les données de démonstration.
5. Si l'adresse attribuée diffère, corriger `APP_URL` dans **Environment** : Render redémarre le site.

## 4. Partager

- Site public : l'adresse Render (ex. `https://hebergement-uam.onrender.com`).
- Back-office : la même adresse suivie de `/admin`, avec `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Ne communiquer ces identifiants qu'aux personnes qui doivent tester la gestion.

Chaque `git push` sur `main` redéploie automatiquement le site.
