<?php

namespace Jawabapp\Localization\Translation;

use Illuminate\Translation\FileLoader;
use Jawabapp\Localization\Models\Translation;

class DatabaseTranslationLoader extends FileLoader
{
    /**
     * Load the messages for the given locale.
     */
    public function load($locale, $group, $namespace = null): array
    {
        // If this is a namespaced group, use the parent file loader
        if ($namespace !== null && $namespace !== '*') {
            return parent::load($locale, $group, $namespace);
        }

        // Try to load from database first
        if (config('localization.cache.enabled', true)) {
            $translations = $this->loadFromDatabase($locale, $group);
            if (!empty($translations)) {
                return $translations;
            }
        }

        // Fallback to file loading
        return parent::load($locale, $group, $namespace);
    }

    /**
     * Load translations from database
     */
    protected function loadFromDatabase(string $locale, string $group): array
    {
        try {
            // Handle JSON translations
            if ($group === '__JSON__') {
                return Translation::locale($locale)
                    ->group('__JSON__')
                    ->pluck('value', 'key')
                    ->toArray();
            }

            // Handle regular group translations
            return Translation::getTranslationsForGroup($group, $locale);
        } catch (\Exception $e) {
            // If database isn't available, return empty array
            return [];
        }
    }

    /**
     * Add a new namespace to the loader.
     */
    public function addNamespace($namespace, $hint): void
    {
        parent::addNamespace($namespace, $hint);
    }

    /**
     * Add a new JSON path to the loader.
     */
    public function addJsonPath($path): void
    {
        parent::addJsonPath($path);
    }

    /**
     * Get the array of supported namespaces.
     */
    public function namespaces(): array
    {
        return $this->hints ?? [];
    }
}