<?php

namespace Jawabapp\Localization\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ValidateConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:validate {--fix : Automatically fix common issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate localization package configuration and setup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fix = $this->option('fix');
        $issues = [];

        $this->info('🔍 Validating localization configuration...');

        // Check configuration file
        $issues = array_merge($issues, $this->validateConfiguration());

        // Check database
        $issues = array_merge($issues, $this->validateDatabase());

        // Check language directories
        $issues = array_merge($issues, $this->validateLanguageDirectories());

        // Check middleware registration
        $issues = array_merge($issues, $this->validateMiddleware());

        // Check routes
        $issues = array_merge($issues, $this->validateRoutes());

        if (empty($issues)) {
            $this->info('✅ All validation checks passed!');
            return Command::SUCCESS;
        }

        $this->error("❌ Found " . count($issues) . " issues:");

        foreach ($issues as $issue) {
            $this->warn("  • {$issue['message']}");

            if ($fix && isset($issue['fix'])) {
                try {
                    $issue['fix']();
                    $this->info("    ✓ Fixed automatically");
                } catch (\Exception $e) {
                    $this->error("    ✗ Failed to fix: " . $e->getMessage());
                }
            } elseif (isset($issue['suggestion'])) {
                $this->line("    💡 {$issue['suggestion']}");
            }
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration(): array
    {
        $issues = [];

        if (!config('localization.supported_locales')) {
            $issues[] = [
                'message' => 'No supported locales configured',
                'suggestion' => 'Add supported locales to config/localization.php'
            ];
        }

        $supportedLocales = config('localization.supported_locales', []);
        $fallbackLocale = config('app.fallback_locale', 'en');

        if (!in_array($fallbackLocale, $supportedLocales)) {
            $issues[] = [
                'message' => "Fallback locale '{$fallbackLocale}' not in supported locales",
                'suggestion' => "Add '{$fallbackLocale}' to supported_locales in config/localization.php"
            ];
        }

        return $issues;
    }

    /**
     * Validate database setup
     */
    private function validateDatabase(): array
    {
        $issues = [];

        try {
            $tableName = config('localization.database.table', 'translations');

            if (!Schema::hasTable($tableName)) {
                $issues[] = [
                    'message' => "Translations table '{$tableName}' does not exist",
                    'suggestion' => 'Run: php artisan migrate'
                ];
            } else {
                // Check table structure
                $expectedColumns = ['id', 'locale', 'group', 'key', 'value', 'metadata', 'created_at', 'updated_at'];

                foreach ($expectedColumns as $column) {
                    if (!Schema::hasColumn($tableName, $column)) {
                        $issues[] = [
                            'message' => "Column '{$column}' missing from {$tableName} table",
                            'suggestion' => 'Run the latest migration or check your database schema'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'suggestion' => 'Check your database configuration'
            ];
        }

        return $issues;
    }

    /**
     * Validate language directories
     */
    private function validateLanguageDirectories(): array
    {
        $issues = [];
        $langPath = app()->langPath();

        if (!File::isDirectory($langPath)) {
            $issues[] = [
                'message' => "Language directory does not exist: {$langPath}",
                'fix' => function () use ($langPath) {
                    File::makeDirectory($langPath, 0755, true);
                }
            ];
        }

        $supportedLocales = config('localization.supported_locales', []);

        foreach ($supportedLocales as $locale) {
            $localePath = "{$langPath}/{$locale}";

            if (!File::isDirectory($localePath)) {
                $issues[] = [
                    'message' => "Locale directory missing: {$localePath}",
                    'fix' => function () use ($localePath) {
                        File::makeDirectory($localePath, 0755, true);
                    }
                ];
            }
        }

        return $issues;
    }

    /**
     * Validate middleware registration
     */
    private function validateMiddleware(): array
    {
        $issues = [];

        // This is a basic check - in a real app, middleware registration
        // would need to be checked in the actual kernel file
        $webMiddleware = '\Jawabapp\Localization\Http\Middleware\Web\Localization';
        $apiMiddleware = '\Jawabapp\Localization\Http\Middleware\Api\Localization';

        if (!class_exists($webMiddleware)) {
            $issues[] = [
                'message' => 'Web localization middleware class not found',
                'suggestion' => 'Ensure the package is properly installed'
            ];
        }

        if (!class_exists($apiMiddleware)) {
            $issues[] = [
                'message' => 'API localization middleware class not found',
                'suggestion' => 'Ensure the package is properly installed'
            ];
        }

        return $issues;
    }

    /**
     * Validate routes
     */
    private function validateRoutes(): array
    {
        $issues = [];

        if (!config('localization.routes.enabled', true)) {
            $issues[] = [
                'message' => 'Package routes are disabled',
                'suggestion' => 'Enable routes in config/localization.php if you want to use the admin interface'
            ];
        }

        return $issues;
    }
}