<?php

namespace Jawabapp\Localization\Http\Middleware\Web;

use Closure;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Make sure current local exists.
        $local = $request->segment(1);

        if ( ! array_key_exists($local, config('localization.locales'))) {
            $segments = $request->segments();
            array_unshift($segments, config('app.fallback_locale'));

            return redirect(implode('/', $segments) . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
        }

        app()->setLocale($local);

        return $next($request);
    }
}