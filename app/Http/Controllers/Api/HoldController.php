<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReservationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\SejourRequest;
use App\Services\HoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoldController extends Controller
{
    /** POST /api/holds : bloque la chambre 15 minutes, après revérification sous verrou. */
    public function store(Request $request, HoldService $holds): JsonResponse
    {
        $donnees = $request->validate([
            'chambre_id' => ['required', 'integer', 'exists:chambres,id'],
            'arrivee' => SejourRequest::regles()['arrivee'],
            'depart' => SejourRequest::regles()['depart'],
            'hold_token' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            ['hold' => $hold, 'jeton' => $jeton] = $holds->creer(
                (int) $donnees['chambre_id'], $donnees['arrivee'], $donnees['depart'], null, $donnees['hold_token'] ?? null,
            );
        } catch (ReservationException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['hold_token' => $jeton, 'expire_le' => $hold->expire_le->toIso8601String()], 201);
    }

    /** DELETE /api/holds : libère la chambre (« Changer de chambre »). */
    public function destroy(Request $request, HoldService $holds): JsonResponse
    {
        $holds->liberer($request->string('hold_token')->toString());

        return response()->json(['released' => true]);
    }
}
