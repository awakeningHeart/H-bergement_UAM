<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\Telephone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    /**
     * POST /api/clients/lookup : ne renvoie que {known: bool}, jamais de donnée personnelle.
     * La réponse est limitée en débit pour freiner l'énumération de numéros.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['telephone' => ['required', 'string', 'max:30']]);

        $telephone = Telephone::normaliser($request->input('telephone'));
        if ($telephone === null) {
            throw ValidationException::withMessages(['telephone' => __('reservation.modal.e1.invalide')]);
        }

        return response()->json(['known' => Client::query()->where('telephone', $telephone)->exists()]);
    }
}
