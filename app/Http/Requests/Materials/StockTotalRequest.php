<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Colour;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Query of `GET materials/stock-total` (INV-21-g, the "Switch to one stock" window): `unit` is a unit code of the workshop and
 * `colours[<colourId>]=<quantity>` the quantity of each colour of the workshop. Validated for the controller; bad input is a 422.
 */
class StockTotalRequest extends FormRequest
{
    /** Reads are authorized by the controller (Gate::authorize). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unit' => ['required', 'string', 'max:32'],
            'colours' => ['required', 'array', 'min:1', 'max:200'],
            // A number from 0 with up to 4 decimals, as the Colours table asks for.
            'colours.*' => ['required', 'regex:/^\d{1,10}(\.\d{1,4})?$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Both lookups are scoped to the workshop: another workshop's unit or colour is refused like an unknown one.
            if (! Unit::query()->where('code', $this->string('unit')->value())->exists()) {
                $validator->errors()->add('unit', __('Choose a unit of your workshop.'));
            }

            $ids = array_keys($this->quantities());

            if (Colour::query()->whereIn('id', $ids)->count() !== count($ids)) {
                $validator->errors()->add('colours', __('Choose colours of your workshop.'));
            }
        }];
    }

    /** @return array<int, string> the typed quantity of each colour, by colour id */
    public function quantities(): array
    {
        $quantities = [];

        foreach ((array) $this->input('colours') as $id => $quantity) {
            $quantities[(int) $id] = (string) $quantity;
        }

        return $quantities;
    }

    public function unitCode(): string
    {
        return $this->string('unit')->value();
    }
}
