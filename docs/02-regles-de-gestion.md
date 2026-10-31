# 02 — Règles de gestion

- **RG01** Une chambre appartient à un site ; un site à une ville.
- **RG02** Deux réservations actives ne peuvent se chevaucher sur une chambre (les holds actifs comptent). Plage [arrivée, départ[.
- **RG03** N° de fiche unique et séquentiel : `UAM-MH-AAAA-NNNNN` (séquence par année, table `sequences`, `lockForUpdate()`).
- **RG04** Téléphone client unique, stocké en E.164 (propaganistas/laravel-phone), par défaut région NE.
- **RG05** Nuitées = date de départ − date d'arrivée ; départ après 12:00 (paramètre `CHECKOUT_HOUR`) ajoute une nuitée.
- **RG06** HT = nuitées × tarif de la chambre (tarif en vigueur à l'arrivée) + repas ; TVA = HT × taux ; TTC = HT + TVA. Entiers FCFA, arrondi à l'entier supérieur sur la TVA.
- **RG07** Paiement dû 48 h après remise des clés, sauf prise en charge par un organisme.
- **RG08** Payeur = client ou organisme ; si organisme, facture à son nom.
- **RG09** Une demande « En attente » non traitée expire après 24 h → « Expirée ».
- **RG10** No-show : arrivée non effectuée le lendemain à 12:00 → « Non présenté », chambre libérée.
- **RG11** Un gestionnaire n'accède qu'aux sites qui lui sont affectés (Policies).
- **RG12** Une facture émise est immuable ; correction par avoir (numérotation séquentielle propre).
- **RG13** Un hold de chambre expire après 15 min ; libération automatique (scheduler).

## Statuts et transitions
En attente → Confirmée | Refusée | Expirée | Annulée
Confirmée → En cours | Annulée | Non présenté
En cours → Terminée
Chaque transition : journalisée (activitylog), notification SMS si pertinent, contrôlée par Policy/permissions.

## Paramètres (table `parametres`)
tva, heure de départ, durées hold/expiration/paiement, nom de l'établissement, coordonnées de facturation, coordonnées bancaires, conditions générales (texte).
