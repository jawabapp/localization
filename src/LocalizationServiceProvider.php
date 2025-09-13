<?php

namespace Jawabapp\Localization;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Jawabapp\Localization\Console\Commands\ExportTranslations;
use Jawabapp\Localization\Console\Commands\ImportTranslations;
use Jawabapp\Localization\Console\Commands\SyncTranslations;
use Jawabapp\Localization\Console\Commands\ClearTranslationsCache;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'localization');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('localization.routes.enabled', true)) {
            $this->registerRoutes();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('localization.php'),
            ], 'localization-config');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/localization'),
            ], 'localization-assets');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'localization-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/localization'),
            ], 'localization-views');

            // Register commands
            $this->commands([
                ExportTranslations::class,
                ImportTranslations::class,
                SyncTranslations::class,
                ClearTranslationsCache::class,
            ]);
        }

        // Add route macro for localized routes
        $this->registerRouteMacros();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'localization');

        // Register the main class to use with the facade
        $this->app->singleton('localization', function () {
            return new Localization();
        });

        // Register custom translator if needed
        if (config('localization.use_database_translations', true)) {
            $this->app->register(TranslationServiceProvider::class);
        }
    }

    /**
     * Register the package routes.
     */
    private function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Get the route group configuration array.
     */
    private function routeConfiguration(): array
    {
        return [
            'prefix' => config('localization.routes.prefix', 'localization'),
            'middleware' => config('localization.routes.middleware', ['web', 'auth']),
            'as' => config('localization.routes.as', 'localization.'),
        ];
    }

    /**
     * Register route macros for localized routes
     */
    private function registerRouteMacros(): void
    {
        // Macro for creating localized route groups
        Route::macro('localized', function ($callback) {
            $locales = config('localization.supported_locales', ['en']);
            $hideDefault = config('localization.url.hide_default', true);
            $defaultLocale = config('app.fallback_locale', 'en');

            foreach ($locales as $locale) {
                if ($hideDefault && $locale === $defaultLocale) {
                    // Default locale without prefix
                    Route::group([
                        'middleware' => ['localization'],
                    ], function () use ($callback, $locale) {
                        app()->setLocale($locale);
                        $callback($locale);
                    });
                } else {
                    // Other locales with prefix
                    Route::group([
                        'prefix' => $locale,
                        'middleware' => ['localization'],
                        'as' => "{$locale}.",
                    ], function () use ($callback, $locale) {
                        app()->setLocale($locale);
                        $callback($locale);
                    });
                }
            }
        });

        // Macro for getting localized route URL
        Route::macro('localizedUrl', function ($name, $locale = null, $parameters = []) {
            $locale = $locale ?? app()->getLocale();
            $hideDefault = config('localization.url.hide_default', true);
            $defaultLocale = config('app.fallback_locale', 'en');

            if ($hideDefault && $locale === $defaultLocale) {
                return route($name, $parameters);
            }

            return route("{$locale}.{$name}", $parameters);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['localization'];
    }
}