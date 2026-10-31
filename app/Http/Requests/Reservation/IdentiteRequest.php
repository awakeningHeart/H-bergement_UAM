<?php

namespace App\Http\Requests\Reservation;

use App\Support\Telephone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/** Fenêtre 1 : téléphone et identité. */
class IdentiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regles(): array
    {
        return [
            'telephone' => ['required', 'string', 'max:30', function (string $attribut, mixed $valeur, Closure $echec) {
                if (Telephone::normaliser((string) $valeur) === null) {
                    $echec(__('reservation.modal.e1.invalide'));
                }
            }],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'type_piece' => ['nullable', 'string', 'max:50'],
            'numero_piece' => ['nullable', 'string', 'max:50'],
            'piece_delivree_le' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function rules(): array
    {
        return self::regles();
    }
}
