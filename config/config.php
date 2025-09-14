<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of locales supported by your application. This should match the
    | directories/files in your lang directory.
    |
    */
    'supported_locales' => ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'tr'],

    /*
    |--------------------------------------------------------------------------
    | Locale Names
    |--------------------------------------------------------------------------
    |
    | Native names for each locale, used in language switchers.
    |
    */
    'locale_names' => [
        'en' => 'English',
        'ar' => 'العربية',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'tr' => 'Türkçe',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic locale detection.
    |
    */
    'detect_browser_locale' => true,
    'store_in_session' => true,
    'store_in_cookie' => true,
    'cookie_name' => 'locale',
    'cookie_duration' => 60 * 24 * 365, // 1 year in minutes

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for package routes.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'localization',
        'middleware' => ['web'],
        'as' => 'localization.',
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | How locales should be handled in URLs.
    |
    */
    'url' => [
        'hide_default' => true, // Hide default locale in URL
        'force_locale_in_url' => false, // Force locale segment in all URLs
        'segment_position' => 1, // Position of locale in URL segments
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Groups
    |--------------------------------------------------------------------------
    |
    | Groups that can be managed through the admin interface.
    |
    */
    'translation_groups' => [
        'auth',
        'pagination',
        'passwords',
        'validation',
        'general',
        'messages',
        'errors',
        'forms',
        'emails',
        'notifications',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for translations.
    |
    */
    'cache' => [
        'enabled' => true,
        'duration' => 60 * 24, // 24 hours in minutes
        'key_prefix' => 'localization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database settings for translations.
    |
    */
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'table' => 'translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Translation Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable database-driven translations. When enabled, the package
    | will check the database first for translations before falling back to files.
    |
    */
    'database_translations' => [
        'enabled' => true, // Enable database translations
        'fallback_to_files' => true, // Fallback to file translations if database fails
        'auto_create_keys' => true, // Automatically create missing translation keys
        'auto_export_to_files' => false, // Automatically export new keys to language files
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for exporting translations.
    |
    */
    'export' => [
        'json' => true, // Enable JSON translations export
        'php' => true, // Enable PHP translations export
        'javascript' => false, // Enable JavaScript translations export
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for importing translations.
    |
    */
    'import' => [
        'overwrite' => false, // Overwrite existing translations on import
        'delete_missing' => false, // Delete translations not in import files
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | How to handle missing translations.
    |
    */
    'fallback' => [
        'use_fallback_locale' => true,
        'use_key_as_fallback' => true,
        'log_missing' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Configuration
    |--------------------------------------------------------------------------
    |
    | SEO-related settings for multilingual sites.
    |
    */
    'seo' => [
        'alternate_links' => true, // Add alternate link tags for SEO
        'x_default' => true, // Add x-default link for unmatched languages
    ],
];