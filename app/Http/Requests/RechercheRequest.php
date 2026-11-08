<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Recherche publique : ville, site, dates, nombre de personnes, type de chambre. */
class RechercheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::regles();
    }

    /** @return array<string, array<int, mixed>> */
    public static function regles(): array
    {
        $aujourdhui = CarbonImmutable::now(config('uam.timezone'))->toDateString();

        return [
            'ville' => ['nullable', 'string', 'max:100', 'exists:villes,slug'],
            'site' => ['nullable', 'string', 'max:100', 'exists:sites,slug'],
            'arrivee' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$aujourdhui],
            'depart' => ['nullable', 'date_format:Y-m-d', 'after:arrivee'],
            'personnes' => ['nullable', 'integer', 'min:1', 'max:10'],
            'type' => ['nullable', 'integer', 'exists:types_chambre,id'],
        ];
    }

    public function arrivee(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('arrivee') ?? now(config('uam.timezone'))->toDateString(), config('uam.timezone'));
    }

    public function depart(): CarbonImmutable
    {
        $depart = $this->validated('depart');

        return $depart ? CarbonImmutable::parse($depart, config('uam.timezone')) : $this->arrivee()->addDays(2);
    }

    public function personnes(): ?int
    {
        $valeur = $this->validated('personnes');

        return $valeur === null ? null : (int) $valeur;
    }

    public function typeId(): ?int
    {
        $valeur = $this->validated('type');

        return $valeur === null ? null : (int) $valeur;
    }
}
