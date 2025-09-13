<?php

namespace Jawabapp\Localization\Console\Commands;

use Illuminate\Console\Command;
use Jawabapp\Localization\Models\Translation;

class SyncTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:sync
                            {--from= : Source locale to sync from}
                            {--to= : Target locale to sync to}
                            {--missing-only : Only sync missing translations}
                            {--overwrite : Overwrite existing translations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync translations between locales';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fromLocale = $this->option('from');
        $toLocale = $this->option('to');
        $missingOnly = $this->option('missing-only');
        $overwrite = $this->option('overwrite');

        if (!$fromLocale) {
            $fromLocale = $this->choice(
                'Select source locale:',
                config('localization.supported_locales', ['en']),
                0
            );
        }

        if (!$toLocale) {
            $supportedLocales = config('localization.supported_locales', ['en']);
            $toLocaleOptions = array_diff($supportedLocales, [$fromLocale]);

            if (empty($toLocaleOptions)) {
                $this->error('No target locale available.');
                return Command::FAILURE;
            }

            $toLocale = $this->choice(
                'Select target locale:',
                array_values($toLocaleOptions),
                0
            );
        }

        $this->info("Syncing translations from '{$fromLocale}' to '{$toLocale}'...");

        try {
            $syncedCount = $this->syncTranslations($fromLocale, $toLocale, $missingOnly, $overwrite);

            $this->info("✅ Successfully synced {$syncedCount} translations!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Sync translations between locales
     */
    private function syncTranslations(string $fromLocale, string $toLocale, bool $missingOnly, bool $overwrite): int
    {
        $sourceTranslations = Translation::locale($fromLocale)->get();

        if ($sourceTranslations->isEmpty()) {
            $this->warn("No translations found for source locale: {$fromLocale}");
            return 0;
        }

        $syncedCount = 0;
        $bar = $this->output->createProgressBar($sourceTranslations->count());
        $bar->start();

        foreach ($sourceTranslations as $sourceTranslation) {
            $targetTranslation = Translation::locale($toLocale)
                ->group($sourceTranslation->group)
                ->key($sourceTranslation->key)
                ->first();

            $shouldSync = false;

            if (!$targetTranslation) {
                // Target translation doesn't exist
                $shouldSync = true;
            } elseif ($missingOnly && empty($targetTranslation->value)) {
                // Target exists but is empty and we only want missing
                $shouldSync = true;
            } elseif ($overwrite) {
                // We want to overwrite existing translations
                $shouldSync = true;
            }

            if ($shouldSync) {
                Translation::updateOrCreate(
                    [
                        'locale' => $toLocale,
                        'group' => $sourceTranslation->group,
                        'key' => $sourceTranslation->key,
                    ],
                    [
                        'value' => $sourceTranslation->value,
                        'metadata' => $sourceTranslation->metadata,
                    ]
                );

                $syncedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $syncedCount;
    }
}