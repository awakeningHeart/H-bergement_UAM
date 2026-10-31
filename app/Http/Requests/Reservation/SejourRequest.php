<?php

namespace App\Http\Requests\Reservation;

use App\Enums\Motif;
use App\Enums\Repas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Fenêtre 2 : séjour et repas. */
class SejourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regles(): array
    {
        $aujourdhui = CarbonImmutable::now(config('uam.timezone'))->toDateString();

        return [
            'chambre_id' => ['required', 'integer', 'exists:chambres,id'],
            'motif' => ['required', Rule::enum(Motif::class)],
            'arrivee' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$aujourdhui],
            'heure' => ['required', 'date_format:H:i'],
            'depart' => ['required', 'date_format:Y-m-d', 'after:arrivee'],
            'repas' => ['nullable', 'array', 'max:3'],
            'repas.*' => [Rule::enum(Repas::class)],
        ];
    }

    public function rules(): array
    {
        return self::regles();
    }

    public function messages(): array
    {
        return [
            'arrivee.after_or_equal' => 'La date d\'arrivée ne peut pas être dans le passé.',
            'depart.after' => 'La date de départ doit être postérieure à la date d\'arrivée.',
        ];
    }
}
