# Procès-verbal de recette — version candidate (lot 4)

Date : 1er novembre 2026 · Établi par : Mohamed (développeur) · À valider par : chef du service informatique de l'UAM.

Ce PV couvre les essais réalisés **par le développeur** sur son poste (MySQL 8.4, PHP 8.4). La recette sur le serveur de l'UAM (colonne « À refaire sur site ») reste à faire avec les données réelles.

## 1. Résultats globaux

| Contrôle | Résultat |
|---|---|
| Tests automatisés (Pest) | 168 tests, tous réussis |
| Style (Laravel Pint) | aucun écart |
| Analyse statique (Larastan, niveau 6) | 0 erreur |
| Essais réels en parallèle (6 processus, base de développement) | un seul blocage de chambre accepté sur 6 demandes simultanées ; 7 numéros de séquence distincts, consécutifs (1 à 7), sans doublon (un 8e processus n'a pas abouti, cause non élucidée) |
| Essai navigateur (poste de bureau) | ouverture du formulaire de réservation, compte à rebours, saisie du téléphone → formulaire « nouveau client » |

## 2. Checklist (`docs/07-checklist-recette.md`)

| # | Point | Preuve | À refaire sur site |
|---|---|---|---|
| 1 | Recherche ville → site → chambre, indisponibles exclues | `SitePublicTest` (accueil, masquage d'une chambre réservée, validation des dates), `DisponibilitesTest` (chevauchements, holds, statuts, filtres) | Parcours avec les vrais sites |
| 2 | Téléphone en premier champ ; lookup = `{known}` seul | `ReservationTest` (réponse exacte `{known}`, aucun nom dans la réponse, débit limité, numéros internationaux) ; champ téléphone autofocus dans le formulaire | — |
| 3 | OTP : 5 min, 3 tentatives, 3 envois / 10 min | `OtpTest` (code haché, expiration, blocage après 3 essais, 3 envois puis refus, usage unique, pas d'énumération) | Avec le vrai fournisseur SMS |
| 4 | Chambre préremplie en lecture seule ; « Changer de chambre » libère le hold | `ReservationModalTest` (chambre affichée dans le bandeau, hold libéré) | — |
| 5 | Deux navigateurs, même chambre : un seul réussit | `ReservationTest` (second hold refusé, revérification sous verrou) + essai réel de 6 processus simultanés | Test à deux appareils |
| 6 | Nuitées / HT / TVA / TTC | `TarificationTest` (départ après 12:00 = nuitée en plus, TVA arrondie au supérieur, repas, tarif à la date d'arrivée) | Valider tarifs et taux de TVA officiels |
| 7 | N° de fiche séquentiel sans doublon | `ReservationTest` (numéros consécutifs, 25 tirages sans trou) + essai réel parallèle | — |
| 8 | PDF avec QR ; scan ouvre la fiche (personnel) | `ReservationTest` (PDF valide via lien signé uniquement ; QR → fiche ; jeton falsifié refusé ; RG11 au scan) | Scanner un QR avec un téléphone |
| 9 | Suivi par téléphone + OTP et par lien | `ReservationTest` (lien opaque, annulation), `ReservationModalTest` (téléphone + code, réponse identique pour un numéro inconnu) | — |
| 10 | Workflow, expirations 24 h, no-show, hold 15 min via scheduler | `WorkflowTest` (15 transitions autorisées/interdites, journal, SMS, expiration, non-présenté, liste des tâches planifiées), `ReservationTest` (purge des holds) | Cron + Supervisor sur le serveur |
| 11 | Gestionnaire limité à ses sites ; Direction en lecture seule | `PoliciesTest`, `BackOfficeWorkflowTest` (RG11 sur fiche, scan, export, factures, paiements ; direction sans action, fiche en consultation seule) | Avec les vrais comptes |
| 12 | Facture immuable, avoir | `FacturationTest` (immuabilité au niveau modèle et policy, avoir négatif, un seul avoir, nouvelle facture après avoir, numérotation distincte, contenu figé, RG08) | Valider le modèle de facture |
| 13 | Journal d'audit, export Excel, tableau de bord | `WorkflowTest` (entrée d'audit avec auteur), `BackOfficeWorkflowTest` (export Excel : colonnes, RG11, téléchargement ; indicateurs et widgets) | — |
| 14 | Mobile 360 px, tests, pint, phpstan | Tests, Pint et PHPStan : conformes. **Contrôle visuel à 360 px non réalisé** (l'outil de navigation n'a pas appliqué le redimensionnement) ; styles responsives écrits (grilles adaptatives, modal plein écran sous 640 px, champs ≥ 44 px) | **À faire** sur téléphone |

## 3. Règles de gestion

| RG | Vérification |
|---|---|
| RG01–RG04 | modèle et contraintes (`ModelesTest`), normalisation E.164 (`ReservationTest`) |
| RG02 | `DisponibilitesTest` |
| RG03 | numérotation `UAM-MH-AAAA-NNNNN` (`ReservationTest`) |
| RG05–RG06 | `TarificationTest` |
| RG07 | échéance 48 h (`FacturationTest`) |
| RG08 | facture au nom de l'organisme (`FacturationTest`) |
| RG09–RG10 | `WorkflowTest` |
| RG11 | `PoliciesTest`, `BackOfficeWorkflowTest`, `FacturationTest`, `ReservationTest` |
| RG12 | `FacturationTest` |
| RG13 | `ReservationTest`, `DisponibilitesTest` |

## 4. Écarts et réserves

1. **Passerelle SMS non branchée** : les SMS sont écrits dans le journal. Indispensable avant ouverture au public.
2. **Données institutionnelles** : tarifs, noms de sites, TVA, conditions générales, coordonnées bancaires sont des valeurs de démonstration ou `[À renseigner]`.
3. **Contrôle visuel mobile (360 px)** et rendu du back-office (tableau de bord, fiche de réservation) : vérifiés par tests mais **non contrôlés à l'œil** ; à faire à la recette.
4. **Création manuelle de réservation** par le personnel : non prévue (les réservations passent par le parcours public, qui garantit disponibilité et numérotation).
5. **Réponses aux avoirs avec paiement déjà encaissé** : le remboursement n'est pas géré (le solde peut devenir négatif) ; à traiter par le service comptable.
6. **E-mail de confirmation** : non prévu (SMS uniquement).

## 5. Signatures

| Rôle | Nom | Date | Signature |
|---|---|---|---|
| Développeur | Mohamed | | |
| Chef du service informatique | | | |
