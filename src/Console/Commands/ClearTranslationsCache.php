<?php

namespace Jawabapp\Localization\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearTranslationsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:clear-cache
                            {--locale= : Clear cache for specific locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear translations cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->option('locale');

        if (!config('localization.cache.enabled', true)) {
            $this->warn('Translation caching is disabled.');
            return Command::SUCCESS;
        }

        try {
            if ($locale) {
                $this->clearLocaleCache($locale);
            } else {
                $this->clearAllCache();
            }

            $this->info('✅ Translation cache cleared successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear cache: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear cache for a specific locale
     */
    private function clearLocaleCache(string $locale): void
    {
        $prefix = config('localization.cache.key_prefix', 'localization');
        $groups = config('localization.translation_groups', []);

        // Clear main locale cache
        Cache::forget("{$prefix}.{$locale}");

        // Clear group-specific caches
        foreach ($groups as $group) {
            Cache::forget("{$prefix}.{$locale}.{$group}");
        }

        $this->line("Cleared cache for locale: {$locale}");
    }

    /**
     * Clear all translation caches
     */
    private function clearAllCache(): void
    {
        $prefix = config('localization.cache.key_prefix', 'localization');
        $locales = config('localization.supported_locales', ['en']);
        $groups = config('localization.translation_groups', []);

        foreach ($locales as $locale) {
            // Clear main locale cache
            Cache::forget("{$prefix}.{$locale}");

            // Clear group-specific caches
            foreach ($groups as $group) {
                Cache::forget("{$prefix}.{$locale}.{$group}");
            }

            $this->line("Cleared cache for locale: {$locale}");
        }

        // If using tags, we could clear all at once
        if (method_exists(Cache::store(), 'tags')) {
            Cache::tags([$prefix])->flush();
            $this->line('Cleared all tagged translation caches');
        }
    }
}