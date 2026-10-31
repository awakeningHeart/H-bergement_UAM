<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

/** POST /reservations : les trois fenêtres validées d'un coup, plus le jeton de hold et la clé d'idempotence. */
class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return IdentiteRequest::regles() + SejourRequest::regles() + FacturationRequest::regles() + [
            'hold_token' => ['required', 'string', 'max:64'],
            'idempotence' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return (new SejourRequest)->messages() + (new FacturationRequest)->messages();
    }
}
