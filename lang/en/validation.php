<?php

// Les messages anglais viennent de Laravel ; seuls les noms des champs sont précisés ici.
return [
    'custom' => [
        'arrivee' => ['after_or_equal' => 'The arrival date cannot be in the past.'],
        'depart' => ['after' => 'The departure date must be after the arrival date.'],
        'conditions' => ['accepted' => 'You must accept the terms to confirm.'],
        'organisme_id' => ['required_if' => 'Choose the organisation paying the invoice.'],
    ],

    'attributes' => [
        'telephone' => 'phone',
        'nom' => 'last name',
        'prenom' => 'first name',
        'email' => 'email',
        'type_piece' => 'ID type',
        'numero_piece' => 'ID number',
        'piece_delivree_le' => 'issue date',
        'motif' => 'purpose',
        'arrivee' => 'arrival date',
        'depart' => 'departure date',
        'heure' => 'arrival time',
        'signature' => 'signature',
        'conditions' => 'terms',
        'organisme_id' => 'organisation',
        'payeur' => 'payer',
        'correspondant_tel' => 'contact\'s phone',
        'correspondant_nom' => 'contact',
    ],
];
