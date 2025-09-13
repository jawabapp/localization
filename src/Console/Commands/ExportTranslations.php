<?php

namespace Jawabapp\Localization\Console\Commands;

use Illuminate\Console\Command;
use Jawabapp\Localization\Libraries\Localization;

class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:export
                            {--locale= : Export translations for specific locale}
                            {--group= : Export translations for specific group}
                            {--format=both : Export format (php, json, both)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translations from database to files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');
        $format = $this->option('format');

        $this->info('Exporting translations...');

        try {
            if ($locale && $group) {
                $this->exportSpecific($locale, $group, $format);
            } elseif ($locale) {
                $this->exportLocale($locale, $format);
            } else {
                Localization::exportTranslations();
            }

            $this->info('✅ Translations exported successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Export failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Export translations for a specific locale
     */
    private function exportLocale(string $locale, string $format): void
    {
        $supportedLocales = config('localization.supported_locales', ['en']);

        if (!in_array($locale, $supportedLocales)) {
            throw new \InvalidArgumentException("Locale '{$locale}' is not supported.");
        }

        $this->line("Exporting translations for locale: {$locale}");

        // Get translations for this locale
        $translations = \Jawabapp\Localization\Models\Translation::locale($locale)->get();

        if ($translations->isEmpty()) {
            $this->warn("No translations found for locale: {$locale}");
            return;
        }

        $phpTranslations = [];
        $jsonTranslations = [];

        foreach ($translations as $translation) {
            if ($translation->group === '__JSON__') {
                $jsonTranslations[$translation->key] = $translation->value;
            } else {
                $this->arraySet($phpTranslations, "{$translation->group}.{$translation->key}", $translation->value);
            }
        }

        // Export based on format
        if (in_array($format, ['php', 'both'])) {
            $this->exportPHPTranslations($locale, $phpTranslations);
        }

        if (in_array($format, ['json', 'both'])) {
            $this->exportJSONTranslations($locale, $jsonTranslations);
        }
    }

    /**
     * Export translations for a specific locale and group
     */
    private function exportSpecific(string $locale, string $group, string $format): void
    {
        $this->line("Exporting translations for locale: {$locale}, group: {$group}");

        $translations = \Jawabapp\Localization\Models\Translation::locale($locale)->group($group)->get();

        if ($translations->isEmpty()) {
            $this->warn("No translations found for locale: {$locale}, group: {$group}");
            return;
        }

        if ($group === '__JSON__' && in_array($format, ['json', 'both'])) {
            $jsonTranslations = [];
            foreach ($translations as $translation) {
                $jsonTranslations[$translation->key] = $translation->value;
            }
            $this->exportJSONTranslations($locale, $jsonTranslations);
        } elseif (in_array($format, ['php', 'both'])) {
            $phpTranslations = [];
            foreach ($translations as $translation) {
                $this->arraySet($phpTranslations, $translation->key, $translation->value);
            }

            $langPath = app()->langPath() . "/{$locale}";
            if (!file_exists($langPath)) {
                mkdir($langPath, 0755, true);
            }

            $content = "<?php\n\nreturn " . var_export($phpTranslations, true) . ";\n";
            file_put_contents("{$langPath}/{$group}.php", $content);
        }
    }

    /**
     * Export PHP translation files
     */
    private function exportPHPTranslations(string $locale, array $translations): void
    {
        $langPath = app()->langPath() . "/{$locale}";

        if (!file_exists($langPath)) {
            mkdir($langPath, 0755, true);
        }

        foreach ($translations as $group => $items) {
            $filePath = "{$langPath}/{$group}.php";
            $content = "<?php\n\nreturn " . var_export($items, true) . ";\n";
            file_put_contents($filePath, $content);
            $this->line("  - Exported: {$filePath}");
        }
    }

    /**
     * Export JSON translation files
     */
    private function exportJSONTranslations(string $locale, array $translations): void
    {
        if (empty($translations)) {
            return;
        }

        $langPath = app()->langPath();

        if (!file_exists($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $filePath = "{$langPath}/{$locale}.json";
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("  - Exported: {$filePath}");
    }

    /**
     * Helper to set array values using dot notation
     */
    private function arraySet(&$array, $key, $value): void
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
}