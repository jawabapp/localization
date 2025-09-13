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
                Console\Commands\ValidateConfiguration::class,
            ]);
        }

        // Add route macro for localized routes
        $this->registerRouteMacros();

        // Register middleware aliases for Laravel 11+
        $this->registerMiddlewareAliases();

        // Override translator after boot
        $this->overrideTranslator();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'localization');

        // Register the main class to use with the facade
        $this->app->singleton('localization', function ($app) {
            return new Libraries\Localization();
        });

        // Schedule translator override to happen after all providers are registered
        $this->app->booted(function ($app) {
            if (config('localization.database_translations.enabled', true)) {
                $loader = $app['translation.loader'];
                $locale = $app->getLocale();

                $customTranslator = new Translation\Translator($loader, $locale);
                $customTranslator->setFallback($app->getFallbackLocale());

                // Override the translator binding
                $app->singleton('translator', function () use ($customTranslator) {
                    return $customTranslator;
                });
            }
        });
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
     * Register middleware aliases for Laravel 11+
     */
    private function registerMiddlewareAliases(): void
    {
        // Check if we're in Laravel 11+ and register middleware aliases
        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            $this->app['router']->aliasMiddleware('localization.web', \Jawabapp\Localization\Http\Middleware\Web\Localization::class);
            $this->app['router']->aliasMiddleware('localization.api', \Jawabapp\Localization\Http\Middleware\Api\Localization::class);
            $this->app['router']->aliasMiddleware('localization', \Jawabapp\Localization\Http\Middleware\Web\Localization::class);
        }
    }

    /**
     * Override the default translator with our custom one
     */
    private function overrideTranslator(): void
    {
        if (config('localization.database_translations.enabled', true)) {
            $loader = $this->app['translation.loader'];
            $locale = $this->app->getLocale();

            $customTranslator = new Translation\Translator($loader, $locale);
            $customTranslator->setFallback($this->app->getFallbackLocale());

            // Force override the translator binding
            $this->app->singleton('translator', function () use ($customTranslator) {
                return $customTranslator;
            });
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['localization'];
    }
}