<?php

namespace App\Http\Requests\Reservation;

use App\Enums\Payeur;
use App\Support\Telephone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Fenêtre 3 : facturation et confirmation. */
class FacturationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regles(): array
    {
        return [
            'correspondant_nom' => ['nullable', 'string', 'max:150'],
            'correspondant_tel' => ['nullable', 'string', 'max:30', function (string $attribut, mixed $valeur, Closure $echec) {
                if (Telephone::normaliser((string) $valeur) === null) {
                    $echec(__('reservation.modal.e1.invalide'));
                }
            }],
            'payeur' => ['required', Rule::enum(Payeur::class)],
            'organisme_id' => ['nullable', 'required_if:payeur,organisme', 'integer', 'exists:organismes,id'],
            'signature' => ['required', 'string', 'min:3', 'max:150'],
            'conditions' => ['accepted'],
        ];
    }

    public function rules(): array
    {
        return self::regles();
    }
}
