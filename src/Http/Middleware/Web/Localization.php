<?php

namespace Jawabapp\Localization\Http\Middleware\Web;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Jawabapp\Localization\Libraries\Localization as LocalizationLib;

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

        // Set the locale
        App::setLocale($locale);

        // Store locale in session and cookie if configured
        if (config('localization.store_in_session', true)) {
            session(['locale' => $locale]);
        }

        if (config('localization.store_in_cookie', true)) {
            Cookie::queue(
                config('localization.cookie_name', 'locale'),
                $locale,
                config('localization.cookie_duration', 60 * 24 * 365)
            );
        }

        // Set default URL locale parameter
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    /**
     * Determine the locale for the request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        $supportedLocales = config('localization.supported_locales', ['en']);
        $hideDefault = config('localization.url.hide_default', true);
        $defaultLocale = config('app.fallback_locale', 'en');
        $segmentPosition = config('localization.url.segment_position', 1);

        // Check URL segment
        $urlLocale = $request->segment($segmentPosition);
        if ($urlLocale && in_array($urlLocale, $supportedLocales)) {
            return $urlLocale;
        }

        // Check if we should redirect to add locale to URL
        if (config('localization.url.force_locale_in_url', false) && !$urlLocale) {
            $locale = $this->detectUserPreferredLocale($request);

            if (!$hideDefault || $locale !== $defaultLocale) {
                $segments = $request->segments();
                array_unshift($segments, $locale);

                $query = $request->getQueryString();
                $redirect = '/' . implode('/', $segments) . ($query ? '?' . $query : '');

                abort(redirect($redirect));
            }
        }

        // Detect user preferred locale
        return $this->detectUserPreferredLocale($request);
    }

    /**
     * Detect user's preferred locale from various sources
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function detectUserPreferredLocale(Request $request): string
    {
        $supportedLocales = config('localization.supported_locales', ['en']);
        $defaultLocale = config('app.fallback_locale', 'en');

        // Priority 1: Query parameter
        if ($request->has('locale')) {
            $queryLocale = $request->get('locale');
            if (in_array($queryLocale, $supportedLocales)) {
                return $queryLocale;
            }
        }

        // Priority 2: Session
        if (config('localization.store_in_session', true) && session()->has('locale')) {
            $sessionLocale = session('locale');
            if (in_array($sessionLocale, $supportedLocales)) {
                return $sessionLocale;
            }
        }

        // Priority 3: Cookie
        if (config('localization.store_in_cookie', true)) {
            $cookieLocale = $request->cookie(config('localization.cookie_name', 'locale'));
            if ($cookieLocale && in_array($cookieLocale, $supportedLocales)) {
                return $cookieLocale;
            }
        }

        // Priority 4: Browser Accept-Language header
        if (config('localization.detect_browser_locale', true)) {
            $browserLocale = $this->detectBrowserLocale($request);
            if ($browserLocale) {
                return $browserLocale;
            }
        }

        // Priority 5: Default locale
        return $defaultLocale;
    }

    /**
     * Detect locale from browser Accept-Language header
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function detectBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        if (!$acceptLanguage) {
            return null;
        }

        $supportedLocales = config('localization.supported_locales', ['en']);

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
        }

        return null;
    }
}