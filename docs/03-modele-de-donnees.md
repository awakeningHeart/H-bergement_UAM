# 03 — Modèle de données (MySQL 8, utf8mb4)

Tables (snake_case ASCII). Clés étrangères, index sur colonnes de recherche, `softDeletes` sur référentiels.

- **villes**(id, nom, slug, latitude, longitude)
- **sites**(id, ville_id, nom, slug, adresse, latitude, longitude, description, telephone, actif)
- **types_chambre**(id, libelle, capacite, equipements json)
- **chambres**(id, site_id, type_chambre_id, numero, etage, description, actif; unique(site_id, numero))
- **tarifs**(id, type_chambre_id nullable, chambre_id nullable, prix_nuit, date_debut, date_fin nullable)
- **tarifs_repas**(id, repas enum[petit_dejeuner,dejeuner,diner], prix)
- **clients**(id, telephone unique E.164, nom, prenom, type_piece, numero_piece, piece_delivree_le, email nullable, titre_fonction, timestamps) — pas de `user_id`
- **otp_codes**(id, telephone, code_hash, expire_le, tentatives, envoye_le, utilise_le)
- **chambre_holds**(id, chambre_id, telephone, arrivee, depart, expire_le, token)
- **organismes**(id, nom, adresse, telephone, email, contact)
- **reservations**(id, numero unique, client_id, chambre_id, organisme_id nullable, motif enum[mission,autres], arrivee, depart, nuitees, correspondant_nom, correspondant_tel, payeur enum[client,organisme], montant_ht, montant_tva, montant_ttc, statut, jeton_suivi_hash, signature, conditions_acceptees_le, timestamps)
- **reservation_repas**(id, reservation_id, repas, quantite, prix_unitaire)
- **factures**(id, numero unique, reservation_id, type enum[facture,avoir], facture_origine_id nullable, montant_ht, montant_tva, montant_ttc, emise_le, pdf_path)
- **paiements**(id, facture_id/reservation_id, montant, mode enum[especes,virement,cheque,mobile_money], reference, recu_par, paye_le)
- **sequences**(id, cle unique, annee, valeur)
- **users / roles / permissions / site_user** (personnel uniquement ; spatie/permission)
- **parametres**(cle, valeur json)
- **audit_logs** (activitylog) ; **photos**(id, morph, chemin, ordre)

Index : reservations(chambre_id, arrivee, depart), reservations(statut), clients(telephone), otp_codes(telephone, expire_le), chambre_holds(chambre_id, expire_le).
Seeders : rôles/permissions, admin initial, paramètres, 2 villes et 3 sites **fictifs marqués `[À renseigner]`**, chambres et tarifs de démonstration.
