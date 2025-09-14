<?php

namespace Jawabapp\Localization\Translation;

use Illuminate\Translation\Translator as BaseTranslator;
use Jawabapp\Localization\Libraries\Localization;

class Translator extends BaseTranslator
{
    /**
     * Get the translation for the given key.
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
        if (! isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            // Here we will get the locale that should be used for the language line. If one
            // was not passed, we will use the default locales which was given to us when
            // the translator was instantiated. Then, we can load the lines and return.
            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            foreach ($locales as $languageLineLocale) {
                if (! is_null($line = $this->getLine(
                    $namespace, $group, $languageLineLocale, $item, $replace
                ))) {
                    return $line;
                }
            }

            $key = $this->handleMissingTranslationKey(
                $key, $replace, $locale, $fallback
            );
        }

        // Auto-create missing key if enabled
        if (config('localization.database_translations.auto_create_keys', false)) {
            $this->createMissingTranslationKey($key, $locale);
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Create a missing translation key in the database
     */
    protected function createMissingTranslationKey(string $key, string $locale): void
    {
        try {
            list($namespace, $group, $itemKey) = app('translator')->parseKey($key);

            if (!in_array($group, config('localization.translation_groups', []))) {
                // Skip groups that are not meant for auto-creation
                return;
            }

            if (config('localization.fallback.log_missing', false)) {
                \Log::info("Attempting to auto-create translation key: {$key} for locale: {$locale} in group: {$group} and item: {$itemKey} namespace: {$namespace}");
            }

            // Check if key already exists to avoid duplicates
            $exists = \Jawabapp\Localization\Models\Translation::locale($locale)
                ->group($group)
                ->where('key', $itemKey)
                ->exists();

            if (!$exists) {
                $translation = \Jawabapp\Localization\Models\Translation::create([
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $itemKey,
                    'value' => $key, // Use the full key as initial value
                    'metadata' => json_encode(['auto_created' => true, 'created_at' => now()->toISOString()])
                ]);

                // Log the creation if debugging is enabled
                if (config('localization.fallback.log_missing', false)) {
                    \Log::info("Auto-created translation key: {$key} for locale: {$locale}");
                }

                // Add the key to the loaded translations immediately
                $this->addToLoadedTranslations($locale, $namespace, $group, $itemKey, $key);
            }
        } catch (\Exception $e) {
            // Silently fail if database operations fail
            if (config('localization.fallback.log_missing', false)) {
                \Log::error("Failed to auto-create translation key: {$key} for locale: {$locale}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Add a translation to the loaded translations cache and optionally to files
     */
    protected function addToLoadedTranslations(string $locale, ?string $namespace, string $group, string $key, string $value): void
    {
        // Add to the loaded array for immediate use
        if ($namespace && $namespace !== '*') {
            // For namespaced translations
            $this->loaded[$namespace][$locale][$group][$key] = $value;
        } else {
            // For non-namespaced translations
            $this->loaded['*'][$locale][$group][$key] = $value;
        }

        // Add to file if auto-export is enabled
        if (config('localization.database_translations.auto_export_to_files', false)) {
            $this->addToFileTranslations($locale, $group, $key, $value);
        }
    }

    /**
     * Add a translation key to the language files
     */
    protected function addToFileTranslations(string $locale, string $group, string $key, string $value): void
    {
        try {
            // Get the language file path
            $langPath = app()->langPath();
            $filePath = "{$langPath}/{$locale}/{$group}.php";

            // Ensure directory exists
            $directory = dirname($filePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Load existing translations or create empty array
            $translations = [];
            if (file_exists($filePath)) {
                $translations = include $filePath;
                if (!is_array($translations)) {
                    $translations = [];
                }
            }

            // Add the new key using dot notation
            $this->arraySet($translations, $key, $value);

            // Write back to file with bracket syntax
            $content = "<?php\n\nreturn " . $this->arrayToString($translations) . ";\n";
            file_put_contents($filePath, $content);

            // Log if debugging is enabled
            if (config('localization.fallback.log_missing', false)) {
                \Log::info("Added translation key to file: {$group}.{$key} for locale: {$locale}");
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            if (config('localization.fallback.log_missing', false)) {
                \Log::error("Failed to add translation to file: {$group}.{$key} for locale: {$locale}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Helper to set array values using dot notation
     */
    protected function arraySet(&$array, $key, $value): void
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
     * Convert array to string with bracket syntax
     */
    protected function arrayToString(array $array, int $indent = 1): string
    {
        $isAssoc = $this->isAssociativeArray($array);
        $indentStr = str_repeat('    ', $indent);
        $result = '[' . "\n";

        foreach ($array as $key => $value) {
            $result .= $indentStr;

            if ($isAssoc) {
                $result .= "'" . addslashes($key) . "' => ";
            }

            if (is_array($value)) {
                $result .= $this->arrayToString($value, $indent + 1);
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
    protected function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}