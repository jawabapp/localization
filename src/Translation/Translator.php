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

        // Fallback to default Laravel translation
        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * Get translation from database
     */
    protected function getFromDatabase(string $key, string $locale): ?string
    {
        if (!config('localization.cache.enabled', true)) {
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
}