<?php

namespace Jawabapp\Localization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getCurrentLocale()
 * @method static array getSupportedLocales()
 * @method static string getLocaleName(string $locale)
 * @method static void setLocale(string $locale)
 * @method static string detectLocale()
 * @method static string routePrefix()
 * @method static array getAlternateUrls()
 * @method static void exportTranslations(array $options = [])
 * @method static void addKeyToTranslation(string $key, string $value, string $locale)
 * @method static array parseAcceptLanguage(string $acceptLanguage)
 * @method static bool isRTL(string $locale = null)
 */
class Localization extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'localization';
    }
}