<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'routes' => [
        'prefix' => 'localization',
        'middleware' => 'auth.session',
    ],
    'locales' => [
        'en' => 'English',
        'ar' => 'Arabic',
        'tr' => 'Turkish',
        'fr' => 'French',
        'ru' => 'Russian',
        'pt' => 'Portugal',
        'es' => 'Spanish',
    ],
    'groups' => [
        'public',
        'mobile',
        'email_template',
        'auth',
        'pagination',
        'passwords',
        'validation',
    ],
];