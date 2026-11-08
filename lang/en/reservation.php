<?php

// Textes vus par le visiteur anglophone. Les clés absentes (workflow, factures, paiements, PDF :
// back-office et documents officiels) retombent sur le français (fallback_locale).
return [
    'sms' => [
        'otp' => 'UAM Guest Houses: your code is :code (valid for :minutes min). Do not share it with anyone.',
        'confirmation' => 'UAM Guest Houses: request no. :numero received. Track or cancel: :lien',
    ],

    // Seuls les messages qu'un client peut voir en annulant depuis la page de suivi.
    'workflow' => [
        'interdit' => 'You are not allowed to perform this action.',
        'transition' => 'Cannot change from “:de” to “:vers”.',
    ],

    'erreurs' => [
        'indisponible' => 'This room is no longer available for these dates. Please choose another room or other dates.',
        'trop_de_holds' => 'You already have several rooms being booked. Complete or release one before choosing another.',
        'hold_expire' => 'The temporary room hold has expired. Please choose your room again.',
        'verification' => 'Please first confirm your number with the code received by SMS.',
        'annulation_impossible' => 'This booking can no longer be cancelled.',
        'trop_de_demandes' => 'Too many requests. Please try again in a few minutes.',
        'otp_trop_d_envois' => 'Too many codes requested. Please try again in a few minutes.',
        'otp_invalide' => 'Incorrect code.',
        'otp_expire' => 'Code expired. Request a new code.',
        'otp_trop_d_essais' => 'Too many attempts. Request a new code.',
    ],

    'modal' => [
        'titre' => 'Booking',
        'ville' => 'Niamey',
        'prix' => ':prix FCFA / night',
        'bloquee' => 'Room held for you',
        'bloquee_aide' => 'Without confirmation within this time, the room will be released.',
        'changer' => 'Change room',
        'fermer' => 'Close',
        'etapes' => ['Identity', 'Stay & meals', 'Billing'],
        'retour' => '← Back',
        'continuer' => 'Continue',
        'confirmer' => 'Confirm booking',
        'envoi' => 'Sending…',

        'e1' => [
            'titre' => 'Phone and identity',
            'telephone' => 'Phone *',
            'placeholder' => '+227 96 00 00 00',
            'invalide' => 'Invalid number: enter a full number (e.g. +227 96 00 00 00).',
            'nouveau' => 'New guest — please complete your details below.',
            'connu' => 'Returning guest — a code has been sent to you by SMS.',
            'verifie' => 'Number confirmed — your details have been filled in.',
            'otp' => 'Code received by SMS (6 digits)',
            'otp_valider' => 'Confirm code',
            'otp_renvoyer' => 'Resend code',
            'nom' => 'Last name *',
            'prenom' => 'First name *',
            'type_piece' => 'ID type',
            'numero_piece' => 'ID number',
            'piece_delivree_le' => 'Issued on',
            'email' => 'Email',
            'pieces' => [
                'Passeport' => 'Passport',
                'Carte d\'identité' => 'Identity card',
                'Permis de conduire' => 'Driving licence',
                'Autre' => 'Other',
            ],
        ],

        'e2' => [
            'titre' => 'Stay and meals',
            'motif' => 'Purpose of stay',
            'arrivee' => 'Arrival',
            'heure' => 'Time',
            'depart' => 'Departure (before :heure)',
            'nuitees' => 'Nights',
            'repas' => 'Eat on site',
            'oui' => 'YES',
            'non' => 'NO',
            'par_jour' => ':prix FCFA / day',
        ],

        'e3' => [
            'titre' => 'Billing and confirmation',
            'correspondant' => 'Contact in Niamey',
            'correspondant_ph' => 'Host person or department',
            'correspondant_tel' => 'Contact\'s phone',
            'facture_a' => 'Invoice to be sent to',
            'moi' => 'Myself',
            'organisme' => 'An organisation',
            'organisme_label' => 'Organisation (paying)',
            'choisir' => 'Choose…',
            'total_ht' => 'Total excl. tax',
            'tva' => 'VAT (:taux %)',
            'total_ttc' => 'Total incl. tax',
            'conditions' => 'I accept the terms: the room must be vacated by :heure at the latest, any night begun is due, and the invoice is paid within :delai hours of key handover, unless billed to an organisation.',
            'signature' => 'Electronic signature — type your full name',
            'nuitees_ligne' => 'Nights · :n × :prix',
            'repas_ligne' => ':libelle · :n × :prix',
        ],

        'e4' => [
            'eyebrow' => 'Booking received',
            'titre' => 'Thank you, :prenom. Your request has been recorded.',
            'texte' => 'It will be reviewed by the guest house manager. A confirmation SMS with your tracking link has been sent to you. Show the QR code on your booking form at reception.',
            'numero' => 'Booking no.',
            'fiche_pdf' => 'Booking form (PDF)',
            'suivi' => 'Track my booking',
        ],

        'hold_expire' => 'The temporary hold has expired. Please choose your room again.',
    ],

    'suivi' => [
        'titre' => 'Your booking',
        'statut' => 'Status',
        'sejour' => 'Stay',
        'montant' => 'Amount incl. tax',
        'annuler' => 'Cancel my request',
        'annuler_confirm' => 'Cancel this request permanently?',
        'annulee' => 'Your request has been cancelled.',
        'fiche_pdf' => 'Download the booking form (PDF)',
        'aucune' => 'No booking found for this number.',
        'telephone' => 'Your phone',
        'envoyer' => 'Get the code',
        'code_envoye' => 'If this number matches a booking, a code has just been sent to you by SMS.',
        'valider' => 'View my bookings',
        'vos_reservations' => 'Your bookings',
        'deconnexion' => 'Leave',
    ],
];
