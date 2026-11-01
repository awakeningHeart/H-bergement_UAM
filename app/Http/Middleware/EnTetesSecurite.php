<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** En-têtes de sécurité HTTP appliqués à toutes les réponses web. */
class EnTetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $reponse = $next($request);

        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        $reponse->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $reponse->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $reponse->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $reponse->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $reponse->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Le back-office Filament et le mode développement (Vite) ont leurs propres ressources : pas de CSP.
        if (! app()->environment('local') && ! $request->is('admin', 'admin/*', 'livewire*', 'filament*')) {
            $reponse->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                // Livewire et Alpine évaluent des expressions : 'unsafe-eval' est nécessaire.
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' data: https://fonts.gstatic.com",
                "img-src 'self' data: blob: https://tile.openstreetmap.org",
                "connect-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ]));
        }

        return $reponse;
    }
}
