<?php

namespace Jawabapp\Localization;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\TranslationServiceProvider as BaseTranslationServiceProvider;
use Jawabapp\Localization\Translation\DatabaseTranslationLoader;
use Jawabapp\Localization\Translation\Translator;

class TranslationServiceProvider extends BaseTranslationServiceProvider
{
    /**
     * Register the translation line loader.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseTranslationLoader($app['files'], $app['path.lang']);
        });
    }

    /**
     * Register the translator.
     */
    protected function registerTranslator(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }
}