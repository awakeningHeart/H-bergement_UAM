<?php

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être une liste.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',

    // Messages propres à un champ (recherche, formulaire de réservation, API).
    'custom' => [
        'arrivee' => ['after_or_equal' => 'La date d\'arrivée ne peut pas être dans le passé.'],
        'depart' => ['after' => 'La date de départ doit être postérieure à la date d\'arrivée.'],
        'conditions' => ['accepted' => 'Vous devez accepter les conditions pour confirmer.'],
        'organisme_id' => ['required_if' => 'Choisissez l\'organisme qui prend en charge la facture.'],
    ],

    'attributes' => [
        'telephone' => 'téléphone',
        'nom' => 'nom',
        'prenom' => 'prénom',
        'email' => 'e-mail',
        'type_piece' => 'type de pièce',
        'numero_piece' => 'numéro de pièce',
        'piece_delivree_le' => 'date de délivrance',
        'motif' => 'motif',
        'arrivee' => 'date d\'arrivée',
        'depart' => 'date de départ',
        'heure' => 'heure d\'arrivée',
        'signature' => 'signature',
        'conditions' => 'conditions',
        'organisme_id' => 'organisme',
        'payeur' => 'payeur',
        'correspondant_tel' => 'téléphone du correspondant',
        'correspondant_nom' => 'correspondant',
    ],
];
