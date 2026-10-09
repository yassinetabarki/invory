<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Enums\UnitDimension;
use App\Models\Unit;
use App\Rules\UniqueActiveName;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edit a unit (ADR-0008). The code never changes. A system (seeded) unit keeps its dimension and factor: only the name and the
 * position change. A custom unit may change dimension and factor only while no material uses it.
 */
class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit && ($this->user()?->can('update', $unit) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $unit = $this->unit();

        $rules = [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:64', new UniqueActiveName('units', $unit)],
            'position' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];

        if ($unit->is_system) {
            return [...$rules, 'dimension' => ['prohibited'], 'factor_to_base' => ['prohibited']];
        }

        $dimension = $this->has('dimension') ? UnitDimension::tryFrom((string) $this->input('dimension')) : $unit->dimension;
        $factor = StoreUnitRequest::factorRules($dimension);

        return [
            ...$rules,
            'dimension' => ['sometimes', 'required', Rule::enum(UnitDimension::class)],
            // Not sent and the dimension is not changing: the factor stays as it is.
            'factor_to_base' => $this->has('dimension') ? $factor : ['sometimes', ...$factor],
        ];
    }

    /**
     * A used unit can't change dimension or factor: every material quantity in it would silently change meaning.
     * Sending the values it already has is fine (a form posts the whole unit).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $unit = $this->unit();

            if ($unit->is_system || $validator->errors()->hasAny(['dimension', 'factor_to_base']) || ! $this->changesDimensionOrFactor($unit)) {
                return;
            }

            // Soft-deleted materials count: they come back with their unit.
            $count = $unit->materials()->withTrashed()->count();

            if ($count > 0) {
                $validator->errors()->add('dimension', trans_choice(
                    'This unit is used by :count material, so its dimension and factor can\'t change.|This unit is used by :count materials, so its dimension and factor can\'t change.',
                    $count,
                ));
            }
        }];
    }

    private function unit(): Unit
    {
        $unit = $this->route('unit');

        // authorize() already refused a request without a bound unit.
        abort_unless($unit instanceof Unit, 404);

        return $unit;
    }

    private function changesDimensionOrFactor(Unit $unit): bool
    {
        $dimension = $this->input('dimension');

        if (is_string($dimension) && $dimension !== $unit->dimension->value) {
            return true;
        }

        $factor = $this->input('factor_to_base');

        if ($factor === null || $factor === '') {
            // Only a change when the unit has a factor today and the request empties it (only valid for package, checked above).
            return $this->has('factor_to_base') && $unit->factor_to_base !== null;
        }

        return $unit->factor_to_base === null
            || ! BigDecimal::of((string) $factor)->isEqualTo($unit->factor_to_base);
    }
}
