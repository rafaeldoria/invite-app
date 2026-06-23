<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $preference = $request->session()->get('locale', $request->cookie('locale'));

        if ($preference !== null) {
            $locale = Locale::normalize($preference);
        } else {
            $browserLocale = $request->getPreferredLanguage(['pt-BR', 'pt', 'en-US', 'en']);
            $locale = Locale::fromBrowserPreference($browserLocale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
