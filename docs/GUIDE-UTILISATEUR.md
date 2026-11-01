# Guide utilisateur

Deux publics : le **réceptionniste / gestionnaire de site** (traitement quotidien) et l'**administrateur DAIG** (paramétrage). La Direction / Rectorat consulte en lecture seule.

Connexion au back-office : `https://<domaine>/admin`.

## 1. Rôles et droits

| Rôle | Ce qu'il peut faire |
|---|---|
| Administrateur | Tout : référentiels, tarifs, utilisateurs, paramètres, factures, audit. Voit tous les sites. |
| Gestionnaire (réceptionniste) | Traite les réservations, remet les clés, encaisse, émet factures et avoirs **de ses sites uniquement**. Consulte les référentiels. |
| Direction | Consulte réservations, tableau de bord, journal d'audit ; exporte. Aucune modification. |

## 2. Le tableau de bord

À la connexion : demandes en attente, arrivées et départs du jour, taux d'occupation du mois, revenus encaissés, impayés, et trois graphiques (statuts, sites, revenus sur 6 mois). Un gestionnaire ne voit que les chiffres de ses sites.

## 3. Traiter une réservation (réceptionniste)

Menu **Réservations**. Ouvrir une réservation (ou scanner le QR code de sa fiche, voir §5) : les boutons en haut de la fiche dépendent du statut.

```
En attente ──► Confirmée ──► En cours ──► Terminée
     │             │
     ├► Refusée    ├► Annulée
     ├► Expirée    └► Non présenté
     └► Annulée
```

| Action | Quand | Effet |
|---|---|---|
| **Confirmer** | demande « En attente » acceptée | SMS de confirmation au client |
| **Refuser** | demande impossible à satisfaire | SMS au client, avec le motif saisi |
| **Arrivée / remise des clés** | le client arrive | statut « En cours », **facture émise automatiquement**, délai de paiement de 48 h démarré |
| **Départ** | le client part | statut « Terminée » |
| **Non présenté** | le client n'est pas venu | la chambre est libérée |
| **Annuler** | demande du client ou du service | libère la chambre, SMS au client |

Sans intervention : une demande « En attente » devient **« Expirée »** après 24 h, et une réservation « Confirmée » dont l'arrivée n'a pas eu lieu devient **« Non présenté »** le lendemain à 12h00.

Chaque changement de statut est enregistré dans le **journal d'audit** avec son auteur et l'heure.

## 4. Paiements, factures et avoirs

- **Enregistrer un paiement** : bouton « Enregistrer un paiement » sur la fiche (montant, mode : espèces, virement, chèque, mobile money ; référence éventuelle). Le montant ne peut pas dépasser le solde dû. Un paiement ne se modifie pas.
- **Échéance** : le paiement est dû 48 h après la remise des clés (champ « Échéance de paiement »), sauf prise en charge par un organisme (la facture est alors établie au nom de l'organisme).
- **Facture** : émise à la remise des clés ; si besoin, bouton « Émettre la facture » sur une réservation confirmée, en cours ou terminée. Le PDF se télécharge dans l'onglet « Factures et avoirs ».
- **Une facture émise ne se modifie ni ne se supprime.** En cas d'erreur : bouton **« Émettre un avoir »** (motif obligatoire) — l'avoir annule la facture en totalité ; on peut ensuite émettre une nouvelle facture.
- Les numéros sont séquentiels et sans trou : `FAC-AAAA-NNNNN` pour les factures, `AV-AAAA-NNNNN` pour les avoirs.

## 5. Arrivée d'un client : le QR code

Le client présente la fiche (PDF ou page de suivi) avec son QR code. Scanner le code avec un téléphone connecté au back-office ouvre directement sa fiche. Si vous n'êtes pas connecté, la connexion vous est demandée ; vous n'ouvrez que les réservations de vos sites.

## 6. Exports

Listes **Réservations** et **Paiements** : bouton « Exporter (Excel) ». L'export respecte les filtres appliqués et vos droits sur les sites.

## 7. Administration (administrateur)

- **Référentiels** : villes, sites (avec photos et personnel affecté), types de chambre, chambres (photos), tarifs, tarifs des repas, organismes.
- **Tarifs** : un tarif est rattaché à un type de chambre ou à une chambre (le tarif de la chambre prime), avec une date de début et une date de fin facultative. Le tarif appliqué est celui en vigueur à la date d'arrivée. Une chambre sans tarif n'est pas proposée en ligne.
- **Utilisateurs** : créer un compte, choisir le rôle, affecter les sites d'un gestionnaire, désactiver un compte.
- **Paramètres** : TVA (en %), heure de départ, délais, coordonnées de facturation et bancaires, conditions générales, visuels de la page d'accueil (`image_accueil`, `image_experience` : chemin d'une image déposée sur le serveur).
- **Journal d'audit** : qui a fait quoi, quand (réservations et changements de statut, paiements, factures, clients, référentiels, utilisateurs, paramètres).

## 8. Ce que voit le client

1. Il choisit ville, site, dates ; la liste ne montre que les chambres libres.
2. « Réserver cette chambre » bloque la chambre pendant 15 minutes et ouvre le formulaire en trois étapes (téléphone et identité ; séjour et repas ; facturation et confirmation).
3. Client connu : un code reçu par SMS lui préremplit ses informations. Nouveau client : il remplit son identité.
4. Il reçoit un SMS de confirmation avec un lien de suivi, peut télécharger la fiche PDF, annuler sa demande, et retrouver ses réservations avec son téléphone et un code SMS.

## 9. Problèmes fréquents

| Constat | Cause probable |
|---|---|
| Le client ne reçoit pas de SMS | passerelle non branchée (`SMS_DRIVER=log`) ou worker `queue:work` arrêté |
| Une chambre n'apparaît pas à la réservation | chambre ou site inactif, sans tarif à la date d'arrivée, ou déjà réservée / bloquée |
| Une demande a disparu de « En attente » | elle a expiré après 24 h (voir statut « Expirée ») |
| Impossible d'ouvrir une réservation | elle appartient à un site qui ne vous est pas affecté |
