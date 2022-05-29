<?php

namespace Jawabapp\Localization\Http\Middleware\Api;

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
        // Check header request and determine localizaton
        $local = $request->header('X-Localization');

        if($local && array_key_exists($local, config('localization.locales'))) {
            // set laravel localization
            app()->setLocale($local);
        }

        return $next($request);
    }
}
