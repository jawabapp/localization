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

        // Try to get from database/cache first
        $translation = $this->getFromDatabase($key, $locale);
        if ($translation !== null) {
            return $this->makeReplacements($translation, $replace);
        }

        // Try to get from parent (files)
        $fileTranslation = parent::get($key, $replace, $locale, false);

        // If translation is found in files, return it
        if ($fileTranslation !== $key) {
            return $fileTranslation;
        }

        // Auto-create missing key if enabled
        if (config('localization.database_translations.auto_create_keys', false)) {
            $this->createMissingTranslationKey($key, $locale);
        }

        // Final fallback
        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * Get translation from database
     */
    protected function getFromDatabase(string $key, string $locale): ?string
    {
        if (!config('localization.database_translations.enabled', true)) {
            return null;
        }

        try {
            // Parse the key to get group and actual key
            if (strpos($key, '.') !== false) {
                [$group, $itemKey] = explode('.', $key, 2);
            } else {
                $group = '__JSON__';
                $itemKey = $key;
            }

            $translations = \Jawabapp\Localization\Models\Translation::getTranslationsForGroup($group, $locale);

            return $translations[$itemKey] ?? null;
        } catch (\Exception $e) {
            // If database isn't available, return null
            return null;
        }
    }

    /**
     * Get the translation for a given key from the JSON translation files.
     */
    public function getFromJson($key, array $replace = [], $locale = null): string|array|null
    {
        $locale = $locale ?: $this->locale;

        // Try to get JSON translation from database first
        try {
            $translation = \Jawabapp\Localization\Models\Translation::locale($locale)
                ->group('__JSON__')
                ->where('key', $key)
                ->value('value');

            if ($translation !== null) {
                return $this->makeReplacements($translation, $replace);
            }
        } catch (\Exception $e) {
            // Continue to file loading if database fails
        }

        // Fallback to file-based JSON translations
        return parent::getFromJson($key, $replace, $locale);
    }

    /**
     * Determine if a translation exists.
     */
    public function has($key, $locale = null, $fallback = true): bool
    {
        $locale = $locale ?: $this->locale;

        // Check database first
        if ($this->hasInDatabase($key, $locale)) {
            return true;
        }

        // Check files
        return parent::has($key, $locale, $fallback);
    }

    /**
     * Check if translation exists in database
     */
    protected function hasInDatabase(string $key, string $locale): bool
    {
        try {
            if (strpos($key, '.') !== false) {
                [$group, $itemKey] = explode('.', $key, 2);
            } else {
                $group = '__JSON__';
                $itemKey = $key;
            }

            return \Jawabapp\Localization\Models\Translation::locale($locale)
                ->group($group)
                ->where('key', $itemKey)
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a missing translation key in the database
     */
    protected function createMissingTranslationKey(string $key, string $locale): void
    {
        try {
            // Parse the key to get group and actual key
            if (strpos($key, '.') !== false) {
                [$group, $itemKey] = explode('.', $key, 2);
            } else {
                $group = '__JSON__';
                $itemKey = $key;
            }

            // Check if key already exists to avoid duplicates
            $exists = \Jawabapp\Localization\Models\Translation::locale($locale)
                ->group($group)
                ->where('key', $itemKey)
                ->exists();

            if (!$exists) {
                \Jawabapp\Localization\Models\Translation::create([
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
            }
        } catch (\Exception $e) {
            // Silently fail if database operations fail
            if (config('localization.fallback.log_missing', false)) {
                \Log::error("Failed to auto-create translation key: {$key} for locale: {$locale}. Error: " . $e->getMessage());
            }
        }
    }
}