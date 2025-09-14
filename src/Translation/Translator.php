<?php

namespace Jawabapp\Localization\Translation;

use Illuminate\Translation\Translator as BaseTranslator;
use Jawabapp\Localization\Libraries\Localization;

class Translator extends BaseTranslator
{
    public function __construct($loader, $locale)
    {
        parent::__construct($loader, $locale);

        $this->missingTranslationKeyCallback = function ($key, $replace, $locale, $fallback) {
            // Auto-create missing key if enabled
            if (config('localization.database_translations.auto_create_keys', false)) {
                $this->createMissingTranslationKey($key, $replace, $locale, $fallback);
            }
        };
    }

    /**
     * Create a missing translation key in the database
     */
    protected function createMissingTranslationKey(string $key, $replace, string $locale, $fallback): void
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
                ->namespace($namespace)
                ->group($group)
                ->where('key', $itemKey)
                ->exists();

            if (!$exists) {
                $translation = \Jawabapp\Localization\Models\Translation::create([
                    'locale' => $locale,
                    'namespace' => $namespace ?: null,
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