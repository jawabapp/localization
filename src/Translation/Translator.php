<?php

namespace Jawabapp\Localization\Translation;

use Illuminate\Translation\Translator as BaseTranslator;
use Jawabapp\Localization\Libraries\Localization;

class Translator extends BaseTranslator
{
    /**
     * Get the translation for the given key.
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array
    {
        $locale = $locale ?: $this->locale;

        // Try to get from parent (files)
        $fileTranslation = parent::get($key, $replace, $locale, false);

        // If translation is found in files, return it
        if ($fileTranslation === $key) {
            // Auto-create missing key if enabled
            if (config('localization.database_translations.auto_create_keys', false)) {
                $this->createMissingTranslationKey($key, $locale);
            }

            // Final fallback
            $fileTranslation = parent::get($key, $replace, $locale, $fallback);
        }

        return $fileTranslation;
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
     * Add a translation to the loaded translations cache
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
    }
}