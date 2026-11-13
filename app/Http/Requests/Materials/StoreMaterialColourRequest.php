<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCostMode;
use App\Models\Colour;
use App\Models\Material;
use App\Validation\WorkshopRule;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreMaterialColourRequest extends FormRequest
{
    /** Adds a colour to the material: the right to update it (MaterialPolicy). */
    public function authorize(): Response
    {
        $material = $this->route('material');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $material instanceof Material ? Gate::inspect('update', $material) : Response::denyAsNotFound();
    }

    /**
     * A colour of the workshop. Whether the material keeps its stock per colour and already has it is the Action's to say, under the row
     * lock; the cost is checked in {@see self::after()}, where the message can name the colour.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'colour_id' => ['bail', 'required', 'integer', WorkshopRule::exists(Colour::class)],
            'unit_cost' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $none = __('Choose a colour.');

        return ['colour_id.required' => $none, 'colour_id.integer' => $none, 'colour_id.exists' => $none];
    }

    /**
     * With cost per colour the new colour needs its cost ("Light wash: Enter a unit cost."), a number from 0 with up to 4 decimals (the column
     * is `decimal(12,4)`). With the same cost for every colour a cost that was sent is ignored.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $material = $this->route('material');

            if (! $material instanceof Material || $material->cost_mode !== MaterialCostMode::PerColour || $validator->errors()->has('colour_id')) {
                return;
            }

            $colour = Colour::query()->find($this->integer('colour_id'));
            $lead = $colour === null ? '' : $colour->name.': ';
            $given = $this->input('unit_cost');

            if ($given === null || $given === '') {
                $validator->errors()->add('unit_cost', $lead.__('Enter a unit cost.'));
            } elseif ((! is_string($given) && ! is_int($given)) || preg_match('/^\d{1,8}(\.\d{1,4})?$/', (string) $given) !== 1) {
                $validator->errors()->add('unit_cost', $lead.__('Enter a number from 0, with up to 4 decimals.'));
            }
        }];
    }
}
