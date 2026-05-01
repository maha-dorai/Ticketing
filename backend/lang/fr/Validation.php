<?php
return [
    'required'  => 'Le champ :attribute est obligatoire.',
    'string'    => 'Le champ :attribute doit être une chaîne de caractères.',
    'email'     => 'Le champ :attribute doit être une adresse email valide.',
    'min'       => ['string' => 'Le champ :attribute doit contenir au moins :min caractères.'],
    'max'       => ['string' => 'Le champ :attribute ne peut pas dépasser :max caractères.'],
    'unique'    => 'Cette adresse email est déjà associée à un compte.',
    'regex'     => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
    'in'        => 'La valeur sélectionnée pour :attribute est invalide.',
    'url'       => 'Le champ :attribute doit être une URL valide.',
    'required_if' => 'Le lien GitHub est obligatoire pour un développeur.',

    'attributes' => [
        'nom'                  => 'nom',
        'prenom'               => 'prénom',
        'email'                => 'email',
        'mot_de_passe'         => 'mot de passe',
        'nouveau_mot_de_passe' => 'nouveau mot de passe',
        'github_link'          => 'lien GitHub',
        'action'               => 'action',
        'role'                 => 'rôle',
    ],
];