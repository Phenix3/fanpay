<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'fr'; // Default

        // 1. User preference
        if ($request->user()?->locale) {
            $locale = $request->user()->locale;
        // 2. Cookie
        } elseif ($request->cookie('locale')) {
            $locale = $request->cookie('locale');
        // 3. Accept-Language header
        } elseif ($request->getPreferredLanguage(['fr', 'en'])) {
            $locale = $request->getPreferredLanguage(['fr', 'en']);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
