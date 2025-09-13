<?php

use Jawabapp\Localization\Libraries\Localization;

if (!function_exists('localization_locale')) {
    /**
     * Get or set the current locale.
     */
    function localization_locale(?string $locale = null): string
    {
        if ($locale !== null) {
            Localization::setLocale($locale);
            return $locale;
        }

        return app()->getLocale();
    }
}

if (!function_exists('localization_supported_locales')) {
    /**
     * Get all supported locales.
     */
    function localization_supported_locales(): array
    {
        return Localization::getSupportedLocales();
    }
}

if (!function_exists('localization_locale_name')) {
    /**
     * Get the native name of a locale.
     */
    function localization_locale_name(string $locale): string
    {
        return Localization::getLocaleName($locale);
    }
}

if (!function_exists('localization_route_prefix')) {
    /**
     * Get the route prefix for the current locale.
     */
    function localization_route_prefix(): string
    {
        return Localization::routePrefix();
    }
}

if (!function_exists('localization_alternate_urls')) {
    /**
     * Get alternate URLs for hreflang tags.
     */
    function localization_alternate_urls(): array
    {
        return Localization::getAlternateUrls();
    }
}

if (!function_exists('localization_is_rtl')) {
    /**
     * Check if the current or given locale is RTL.
     */
    function localization_is_rtl(?string $locale = null): bool
    {
        return Localization::isRTL($locale);
    }
}

if (!function_exists('localization_detect')) {
    /**
     * Detect the best locale for the current request.
     */
    function localization_detect(): string
    {
        return Localization::detectLocale();
    }
}

if (!function_exists('localized_route')) {
    /**
     * Generate a localized route URL.
     */
    function localized_route(string $name, ?string $locale = null, array $parameters = []): string
    {
        $locale = $locale ?? app()->getLocale();
        $hideDefault = config('localization.url.hide_default', true);
        $defaultLocale = config('app.fallback_locale', 'en');

        if ($hideDefault && $locale === $defaultLocale) {
            return route($name, $parameters);
        }

        return route("{$locale}.{$name}", $parameters);
    }
}

if (!function_exists('current_route_localized')) {
    /**
     * Get the current route in different locales.
     */
    function current_route_localized(): array
    {
        $currentRoute = optional(request()->route())->getName();
        $urls = [];

        if ($currentRoute) {
            $parameters = request()->route() ? request()->route()->parameters() : [];

            foreach (Localization::getSupportedLocales() as $locale) {
                try {
                    $urls[$locale] = localized_route($currentRoute, $locale, $parameters);
                } catch (\Exception $e) {
                    // Skip if route doesn't exist for this locale
                    continue;
                }
            }
        }

        return $urls;
    }
}

if (!function_exists('translation_progress')) {
    /**
     * Get translation progress statistics.
     */
    function translation_progress(?string $locale = null): array
    {
        $stats = \Jawabapp\Localization\Models\Translation::getStatistics();

        if ($locale) {
            return $stats[$locale] ?? [
                'total' => 0,
                'translated' => 0,
                'untranslated' => 0,
                'percentage' => 0
            ];
        }

        return $stats;
    }
}

if (!function_exists('add_translation')) {
    /**
     * Add a translation to the database.
     */
    function add_translation(string $key, string $value, ?string $locale = null, ?string $group = null): void
    {
        $locale = $locale ?: app()->getLocale();
        $group = $group ?: '__JSON__';

        Localization::addKeyToTranslation($key, $value, $locale, $group);
    }
}

if (!function_exists('locale_flag')) {
    /**
     * Get the flag emoji or flag icon class for a locale.
     */
    function locale_flag(string $locale, bool $emoji = true): string
    {
        $flags = [
            'en' => $emoji ? '🇺🇸' : 'flag-us',
            'ar' => $emoji ? '🇸🇦' : 'flag-sa',
            'es' => $emoji ? '🇪🇸' : 'flag-es',
            'fr' => $emoji ? '🇫🇷' : 'flag-fr',
            'de' => $emoji ? '🇩🇪' : 'flag-de',
            'it' => $emoji ? '🇮🇹' : 'flag-it',
            'pt' => $emoji ? '🇵🇹' : 'flag-pt',
            'ru' => $emoji ? '🇷🇺' : 'flag-ru',
            'zh' => $emoji ? '🇨🇳' : 'flag-cn',
            'ja' => $emoji ? '🇯🇵' : 'flag-jp',
            'ko' => $emoji ? '🇰🇷' : 'flag-kr',
            'tr' => $emoji ? '🇹🇷' : 'flag-tr',
        ];

        return $flags[$locale] ?? ($emoji ? '🌐' : 'flag-generic');
    }
}

if (!function_exists('format_locale_date')) {
    /**
     * Format a date according to locale preferences.
     */
    function format_locale_date($date, ?string $locale = null, string $format = 'medium'): string
    {
        $locale = $locale ?: app()->getLocale();

        if (!$date instanceof \Carbon\Carbon) {
            $date = \Carbon\Carbon::parse($date);
        }

        return $date->locale($locale)->isoFormat(
            match($format) {
                'short' => 'L',
                'long' => 'LLLL',
                'full' => 'dddd, MMMM Do YYYY, h:mm:ss a',
                default => 'LLL', // medium
            }
        );
    }
}