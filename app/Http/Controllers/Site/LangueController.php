<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LangueController extends Controller
{
    public function __invoke(Request $request, string $langue): RedirectResponse
    {
        abort_unless(in_array($langue, config('uam.langues'), true), 404);

        $request->session()->put('langue', $langue);

        // Retour à la page d'origine, uniquement si elle appartient à l'application (pas de redirection ouverte via Referer).
        $racine = url('/');
        $precedente = url()->previous($racine);
        $interne = $precedente === $racine || str_starts_with($precedente, $racine.'/') || str_starts_with($precedente, $racine.'?');

        return redirect()->to($interne ? $precedente : route('accueil'));
    }
}
