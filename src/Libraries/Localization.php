<?php

namespace Jawabapp\Localization\Libraries;

use Jawabapp\Localization\Models\Translation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class Localization
{
    /**
     * Get route prefix based on locale
     */
    public static function routePrefix($locale = null): ?string
    {
        if (empty($locale) || !is_string($locale)) {
            $locale = request()->segment(1);
        }

        $supportedLocales = config('localization.supported_locales', []);

        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            return $locale;
        }

        // Try to detect best locale
        $detectedLocale = self::detectLocale();
        App::setLocale($detectedLocale);

        return $detectedLocale !== config('app.fallback_locale') ? $detectedLocale : null;
    }

    /**
     * Detect the best locale for the user
     */
    public static function detectLocale(): string
    {
        // Check session
        if (session()->has('locale')) {
            $sessionLocale = session('locale');
            if (in_array($sessionLocale, config('localization.supported_locales', []))) {
                return $sessionLocale;
            }
        }

        // Check browser accept language
        if (config('localization.detect_browser_locale', true)) {
            $browserLocale = self::detectBrowserLocale();
            if ($browserLocale) {
                return $browserLocale;
            }
        }

        return config('app.fallback_locale', 'en');
    }

    /**
     * Detect browser preferred locale
     */
    private static function detectBrowserLocale(): ?string
    {
        $acceptLanguage = request()->server('HTTP_ACCEPT_LANGUAGE');
        if (!$acceptLanguage) {
            return null;
        }

        $supportedLocales = config('localization.supported_locales', []);

        // Parse Accept-Language header
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);

        if (count($matches[1])) {
            $languages = array_combine($matches[1], $matches[4]);

            foreach ($languages as $lang => $priority) {
                $languages[$lang] = $priority ?: 1;
            }

            arsort($languages, SORT_NUMERIC);

            foreach ($languages as $lang => $priority) {
                $lang = substr($lang, 0, 2); // Get language code without region
                if (in_array($lang, $supportedLocales)) {
                    return $lang;
                }
            }
        }

        return null;
    }

    /**
     * Set the application locale
     */
    public static function setLocale(string $locale): void
    {
        if (in_array($locale, config('localization.supported_locales', []))) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }
    }

    /**
     * Get all supported locales
     */
    public static function getSupportedLocales(): array
    {
        return config('localization.supported_locales', ['en']);
    }

    /**
     * Get locale native name
     */
    public static function getLocaleName(string $locale): string
    {
        $names = config('localization.locale_names', []);
        return $names[$locale] ?? strtoupper($locale);
    }

    /**
     * Generate translation keys for model attributes
     */
    public static function generate($class, &$attributes, $old = null): void
    {
        $shouldExport = false;
        $key = uniqid();

        foreach ($attributes as $field => $value) {
            if (preg_match('/_key$/', $field)) {
                if ($value) {
                    $attributes[$field] = empty($old[$field])
                        ? self::getTranslationKey($class, $field, $key)
                        : $old[$field];

                    self::addKeyToTranslation($attributes[$field], $value);
                    $shouldExport = true;
                } else {
                    $attributes[$field] = '';

                    if (!empty($old[$field])) {
                        Translation::where('key', $old[$field])->delete();
                        $shouldExport = true;
                    }
                }
            }
        }

        if ($shouldExport && !App::runningInConsole()) {
            self::exportTranslations();
        }
    }

    /**
     * Delete translation keys for model
     */
    public static function delete($old): void
    {
        $shouldExport = false;

        foreach ($old as $field => $value) {
            if (preg_match('/_key$/', $field) && $value) {
                Translation::where('key', $value)->delete();
                $shouldExport = true;
            }
        }

        if ($shouldExport && !App::runningInConsole()) {
            self::exportTranslations();
        }
    }

    /**
     * Add a translation key
     */
    public static function addKeyToTranslation($key, $value = null, $locale = null): bool
    {
        $locale = $locale ?? App::getLocale();

        [$namespace, $group, $item] = app('translator')->parseKey($key);

        if (!in_array($group, config('localization.translation_groups', []))) {
            return false;
        }

        Translation::updateOrCreate(
            [
                'key' => $key,
                'locale' => $locale,
            ],
            [
                'value' => $value ?? $item,
                'group' => $group,
            ]
        );

        // Clear translation cache
        Cache::forget("translations.{$locale}");

        return true;
    }

    /**
     * Get translation file name for a model class
     */
    public static function getTranslationFileName($class): string
    {
        return 'db_' . strtolower(class_basename($class));
    }

    /**
     * Get translation key for a model field
     */
    private static function getTranslationKey($class, $field, $key): string
    {
        return self::getTranslationFileName($class) . '.' . $field . '_' . $key;
    }

    /**
     * Export all translations to files
     */
    public static function exportTranslations(): void
    {
        $locales = config('localization.supported_locales', ['en']);

        foreach ($locales as $locale) {
            $translations = Translation::where('locale', $locale)->get();

            $phpTranslations = [];
            $jsonTranslations = [];

            foreach ($translations as $translation) {
                if ($translation->group === '__JSON__') {
                    $jsonTranslations[$translation->key] = $translation->value;
                } else {
                    self::arraySet($phpTranslations, "{$translation->group}.{$translation->key}", $translation->value);
                }
            }

            // Export PHP translations
            if (!empty($phpTranslations)) {
                self::exportPHPTranslations($locale, $phpTranslations);
            }

            // Export JSON translations
            if (!empty($jsonTranslations)) {
                self::exportJSONTranslations($locale, $jsonTranslations);
            }
        }
    }

    /**
     * Export PHP translation files
     */
    private static function exportPHPTranslations(string $locale, array $translations): void
    {
        $langPath = self::getLangPath($locale);

        foreach ($translations as $group => $items) {
            $filePath = "{$langPath}/{$group}.php";

            if (!File::isDirectory($langPath)) {
                File::makeDirectory($langPath, 0755, true);
            }

            $content = "<?php\n\nreturn " . self::arrayToString($items) . ";\n";
            File::put($filePath, $content);
        }
    }

    /**
     * Export JSON translation files
     */
    private static function exportJSONTranslations(string $locale, array $translations): void
    {
        $langPath = self::getLangPath();

        if (!File::isDirectory($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $filePath = "{$langPath}/{$locale}.json";
        File::put($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Get the language path based on Laravel version
     */
    private static function getLangPath(?string $locale = null): string
    {
        // Laravel 9+ uses lang/ in root, older versions use resources/lang/
        $basePath = App::langPath();

        if ($locale) {
            return "{$basePath}/{$locale}";
        }

        return $basePath;
    }

    /**
     * Import translations from files
     */
    public static function importTranslations(): void
    {
        $locales = config('localization.supported_locales', ['en']);

        foreach ($locales as $locale) {
            // Import PHP translations
            self::importPHPTranslations($locale);

            // Import JSON translations
            self::importJSONTranslations($locale);
        }
    }

    /**
     * Import PHP translation files
     */
    private static function importPHPTranslations(string $locale): void
    {
        $langPath = self::getLangPath($locale);

        if (!File::isDirectory($langPath)) {
            return;
        }

        $files = File::files($langPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $translations = include $file->getPathname();

            if (!is_array($translations)) {
                continue;
            }

            self::importTranslationGroup($locale, $group, $translations);
        }
    }

    /**
     * Import JSON translation files
     */
    private static function importJSONTranslations(string $locale): void
    {
        $filePath = self::getLangPath() . "/{$locale}.json";

        if (!File::exists($filePath)) {
            return;
        }

        $translations = json_decode(File::get($filePath), true);

        if (!is_array($translations)) {
            return;
        }

        foreach ($translations as $key => $value) {
            Translation::updateOrCreate(
                [
                    'locale' => $locale,
                    'group' => '__JSON__',
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    /**
     * Import a translation group
     */
    private static function importTranslationGroup(string $locale, string $group, array $translations, string $prefix = ''): void
    {
        foreach ($translations as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                self::importTranslationGroup($locale, $group, $value, $fullKey);
            } else {
                Translation::updateOrCreate(
                    [
                        'locale' => $locale,
                        'group' => $group,
                        'key' => $fullKey,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    /**
     * Get available locales from the system
     */
    public static function getAvailableLocales(): array
    {
        $locales = [];
        $langPath = self::getLangPath();

        // Check for directories (PHP translations)
        if (File::isDirectory($langPath)) {
            $directories = File::directories($langPath);
            foreach ($directories as $dir) {
                $locales[] = basename($dir);
            }

            // Check for JSON files
            $files = File::files($langPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $locale = $file->getFilenameWithoutExtension();
                    if (!in_array($locale, $locales)) {
                        $locales[] = $locale;
                    }
                }
            }
        }

        return array_unique($locales);
    }

    /**
     * Helper to set array values using dot notation
     */
    private static function arraySet(&$array, $key, $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Get localized route
     */
    public static function localizedRoute(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();

        if ($locale !== config('app.fallback_locale')) {
            $parameters = array_merge(['locale' => $locale], $parameters);
        }

        return route($name, $parameters);
    }

    /**
     * Get alternate locale URLs for SEO
     */
    public static function getAlternateUrls(): array
    {
        $urls = [];
        $currentRoute = request()->route();

        if (!$currentRoute) {
            return $urls;
        }

        $currentParameters = $currentRoute->parameters();

        foreach (self::getSupportedLocales() as $locale) {
            $parameters = $currentParameters;

            if ($locale !== config('app.fallback_locale')) {
                $parameters['locale'] = $locale;
            } else {
                unset($parameters['locale']);
            }

            $urls[$locale] = url($currentRoute->uri(), $parameters);
        }

        return $urls;
    }

    /**
     * Convert array to string with bracket syntax
     */
    private static function arrayToString(array $array, int $indent = 1): string
    {
        $isAssoc = self::isAssociativeArray($array);
        $indentStr = str_repeat('    ', $indent);
        $result = '[' . "\n";

        foreach ($array as $key => $value) {
            $result .= $indentStr;

            if ($isAssoc) {
                $result .= "'" . addslashes($key) . "' => ";
            }

            if (is_array($value)) {
                $result .= self::arrayToString($value, $indent + 1);
            } elseif (is_string($value)) {
                $result .= "'" . addslashes($value) . "'";
            } elseif (is_null($value)) {
                $result .= 'null';
            } elseif (is_bool($value)) {
                $result .= $value ? 'true' : 'false';
            } else {
                $result .= $value;
            }

            $result .= ",\n";
        }

        $result .= str_repeat('    ', $indent - 1) . ']';
        return $result;
    }

    /**
     * Check if array is associative
     */
    private static function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}