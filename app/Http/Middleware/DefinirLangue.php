<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Langue du site public, choisie par le visiteur et gardée en session (le back-office reste en français). */
class DefinirLangue
{
    public function handle(Request $request, Closure $next): Response
    {
        $langue = $request->session()->get('langue');

        if (is_string($langue) && in_array($langue, config('uam.langues'), true)) {
            app()->setLocale($langue);
        }

        return $next($request);
    }
}
