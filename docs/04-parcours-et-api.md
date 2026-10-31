# 04 — Parcours de réservation et API

## Parcours public
1. Accueil : sélection **ville → site → dates** ; liste des chambres disponibles (cartes) + carte Leaflet.
2. Clic « Réserver cette chambre » → crée un **hold** (`POST /api/holds`) et ouvre le modal.
3. Modal en 3 fenêtres (composant Livewire multi-étapes, validation Form Request par étape). Bandeau permanent : Ville · Site / Chambre n° / « Changer de chambre ».
   - **Fenêtre 1 — Téléphone + Identité** : téléphone en premier champ ; au blur/saisie complète → `POST /api/clients/lookup` → `{known}`. Nouveau : formulaire identité (nom, prénom, type et n° de pièce, délivrée le, e-mail). Connu : envoi OTP ; après vérification, identité préremplie modifiable. Aucune donnée personnelle avant OTP.
   - **Fenêtre 2 — Séjour + Repas** : motif (Mission/Autres), chambre (lecture seule), arrivée, départ, heure, nuitées auto, repas (petit-déjeuner/déjeuner/dîner, quantité).
   - **Fenêtre 3 — Facturation + Confirmation** : correspondant à Niamey, payeur (client/organisme + champs organisme), récapitulatif HT/TVA/TTC, conditions, signature électronique, « Envoyer ma demande ».
4. Confirmation : N° de fiche, lien de suivi, PDF avec QR, SMS.

## Routes API (JSON, throttle, CSRF)
| Route | Rôle |
|---|---|
| `GET /api/sites?ville=` | sites d'une ville |
| `GET /api/chambres/disponibles?site=&arrivee=&depart=` | disponibilités |
| `POST /api/clients/lookup` {telephone} | `{known: bool}` uniquement |
| `POST /api/otp/send` | envoi OTP (3 / 10 min) |
| `POST /api/otp/verify` | vérifie (3 tentatives, 5 min), renvoie identité si client connu |
| `POST /api/holds` | hold 15 min, revérifie dispo avec verrou |
| `POST /api/reservations/estimate` | calcul HT/TVA/TTC |
| `POST /reservations` | création finale en transaction (`FOR UPDATE`) |
| `GET /suivi/{jeton}` | suivi par lien signé/opaque hashé |
| `GET /fiche/{numero}/pdf` | PDF avec QR (accès signé) |
| `GET /admin/scan/{token}` | ouvre la fiche pour le personnel |

## Pièges à traiter
Double clic sur envoi (idempotence), hold expiré pendant la saisie (message et retour au choix), fuseaux horaires, numéros nigériens/étrangers (région par défaut NE mais accepter l'international), énumération de numéros (réponse uniforme + throttle), OTP rejoué.
