<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\OtpService;
use App\Support\Telephone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    /** POST /api/otp/send : réponse identique que le numéro soit connu ou non. */
    public function send(Request $request, OtpService $otp): JsonResponse
    {
        $telephone = $this->telephone($request);

        if (! $otp->envoyer($telephone)) {
            return response()->json(['message' => __('reservation.erreurs.otp_trop_d_envois')], 429);
        }

        return response()->json(['sent' => true]);
    }

    /** POST /api/otp/verify : renvoie l'identité du client connu seulement après un code valide. */
    public function verify(Request $request, OtpService $otp): JsonResponse
    {
        $telephone = $this->telephone($request);
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $resultat = $otp->verifier($telephone, (string) $request->input('code'));
        if ($resultat !== OtpService::OK) {
            return response()->json([
                'verified' => false,
                'message' => __('reservation.erreurs.otp_'.$resultat),
            ], $resultat === OtpService::TROP_D_ESSAIS ? 429 : 422);
        }

        $client = Client::query()->where('telephone', $telephone)->first();

        return response()->json([
            'verified' => true,
            'identite' => $client?->only(['nom', 'prenom', 'type_piece', 'numero_piece', 'piece_delivree_le', 'email']),
        ]);
    }

    private function telephone(Request $request): string
    {
        $request->validate(['telephone' => ['required', 'string', 'max:30']]);
        $telephone = Telephone::normaliser($request->input('telephone'));
        if ($telephone === null) {
            throw ValidationException::withMessages(['telephone' => __('reservation.modal.e1.invalide')]);
        }

        return $telephone;
    }
}
