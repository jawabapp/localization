<?php

namespace Jawabapp\Localization\Console\Commands;

use Illuminate\Console\Command;
use Jawabapp\Localization\Models\Translation;
use Illuminate\Support\Facades\File;

class ImportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:import
                            {--locale= : Import translations for specific locale}
                            {--group= : Import translations for specific group}
                            {--overwrite : Overwrite existing translations}
                            {--delete-missing : Delete translations not found in import files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from files to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');
        $overwrite = $this->option('overwrite');
        $deleteMissing = $this->option('delete-missing');

        $this->info('Importing translations...');

        try {
            $importedCount = 0;

            if ($locale && $group) {
                $importedCount = $this->importSpecific($locale, $group, $overwrite);
            } elseif ($locale) {
                $importedCount = $this->importLocale($locale, $overwrite, $deleteMissing);
            } else {
                $importedCount = $this->importAll($overwrite, $deleteMissing);
            }

            $this->info("✅ Successfully imported {$importedCount} translations!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Import all translations
     */
    private function importAll(bool $overwrite, bool $deleteMissing): int
    {
        $locales = config('localization.supported_locales', ['en']);
        $totalCount = 0;

        foreach ($locales as $locale) {
            $this->line("Importing translations for locale: {$locale}");
            $totalCount += $this->importLocale($locale, $overwrite, $deleteMissing);
        }

        return $totalCount;
    }

    /**
     * Import translations for a specific locale
     */
    private function importLocale(string $locale, bool $overwrite, bool $deleteMissing): int
    {
        $count = 0;

        // Import PHP translations
        $count += $this->importPHPTranslations($locale, $overwrite);

        // Import JSON translations
        $count += $this->importJSONTranslations($locale, $overwrite);

        // Delete missing translations if requested
        if ($deleteMissing) {
            $this->deleteMissingTranslations($locale);
        }

        return $count;
    }

    /**
     * Import translations for a specific locale and group
     */
    private function importSpecific(string $locale, string $group, bool $overwrite): int
    {
        $count = 0;

        if ($group === '__JSON__') {
            $count = $this->importJSONTranslations($locale, $overwrite);
        } else {
            $langPath = app()->langPath() . "/{$locale}";
            $filePath = "{$langPath}/{$group}.php";

            if (!File::exists($filePath)) {
                throw new \InvalidArgumentException("Translation file not found: {$filePath}");
            }

            $translations = include $filePath;
            if (!is_array($translations)) {
                throw new \InvalidArgumentException("Invalid translation file format: {$filePath}");
            }

            $count = Translation::import($translations, $locale, $group, $overwrite);
        }

        return $count;
    }

    /**
     * Import PHP translation files
     */
    private function importPHPTranslations(string $locale, bool $overwrite): int
    {
        $langPath = app()->langPath() . "/{$locale}";
        $count = 0;

        if (!File::isDirectory($langPath)) {
            return 0;
        }

        $files = File::files($langPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $translations = include $file->getPathname();

            if (!is_array($translations)) {
                $this->warn("Skipping invalid translation file: {$file->getPathname()}");
                continue;
            }

            $imported = Translation::import($translations, $locale, $group, $overwrite);
            $count += $imported;

            $this->line("  - Imported {$imported} translations from: {$file->getFilename()}");
        }

        return $count;
    }

    /**
     * Import JSON translation files
     */
    private function importJSONTranslations(string $locale, bool $overwrite): int
    {
        $filePath = app()->langPath() . "/{$locale}.json";

        if (!File::exists($filePath)) {
            return 0;
        }

        $content = File::get($filePath);
        $translations = json_decode($content, true);

        if (!is_array($translations)) {
            $this->warn("Skipping invalid JSON translation file: {$filePath}");
            return 0;
        }

        $count = Translation::import($translations, $locale, '__JSON__', $overwrite);
        $this->line("  - Imported {$count} JSON translations");

        return $count;
    }

    /**
     * Delete translations that are not found in import files
     */
    private function deleteMissingTranslations(string $locale): void
    {
        // This is a placeholder for the delete missing functionality
        // Implementation would require tracking which translations were imported
        // and deleting any that weren't found in the files
        $this->warn('Delete missing translations functionality is not yet implemented.');
    }
}