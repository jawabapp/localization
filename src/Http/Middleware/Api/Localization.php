<?php

namespace Jawabapp\Localization\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->determineLocale($request);

        // Set Laravel localization
        App::setLocale($locale);

        // Add locale to response headers
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Content-Language', $locale);
        }

        return $response;
    }

    /**
     * Determine the locale for the API request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        $supportedLocales = config('localization.supported_locales', ['en']);
        $defaultLocale = config('app.fallback_locale', 'en');

        // Priority 1: Custom header (X-Localization or Accept-Language)
        $customHeader = $request->header('X-Localization') ?? $request->header('X-Locale');
        if ($customHeader && in_array($customHeader, $supportedLocales)) {
            return $customHeader;
        }

        // Priority 2: Query parameter
        if ($request->has('locale')) {
            $queryLocale = $request->get('locale');
            if (in_array($queryLocale, $supportedLocales)) {
                return $queryLocale;
            }
        }

        // Priority 3: Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $browserLocale = $this->parseAcceptLanguage($acceptLanguage, $supportedLocales);
            if ($browserLocale) {
                return $browserLocale;
            }
        }

        // Priority 4: Default locale
        return $defaultLocale;
    }

    /**
     * Parse Accept-Language header and find best match
     *
     * @param  string  $acceptLanguage
     * @param  array  $supportedLocales
     * @return string|null
     */
    protected function parseAcceptLanguage(string $acceptLanguage, array $supportedLocales): ?string
    {
        // Parse Accept-Language header
        preg_match_all(
            '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
            $acceptLanguage,
            $matches
        );

        if (!count($matches[1])) {
            return null;
        }

        $languages = array_combine($matches[1], $matches[4]);

        // Set default quality to 1 for languages without q value
        foreach ($languages as $lang => $quality) {
            $languages[$lang] = $quality ?: 1;
        }

        // Sort by quality (priority)
        arsort($languages, SORT_NUMERIC);

        // Check each language against supported locales
        foreach ($languages as $lang => $quality) {
            // Try exact match first
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }

            // Try language code without region (e.g., "en" from "en-US")
            $langCode = substr($lang, 0, 2);
            if (in_array($langCode, $supportedLocales)) {
                return $langCode;
            }

            // Try to find locale that starts with the language code
            foreach ($supportedLocales as $locale) {
                if (str_starts_with($locale, $langCode)) {
                    return $locale;
                }
            }
        }

        return null;
    }
}