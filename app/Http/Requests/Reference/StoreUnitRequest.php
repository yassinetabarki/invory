<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Enums\UnitDimension;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Unit::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Immutable and unique among ALL rows, archived ones included (a code is never reused).
            'code' => ['required', 'string', 'max:16', 'regex:/^[a-z0-9_]+$/', Rule::unique('units', 'code')],
            'name' => ['required', 'string', 'max:64', Rule::unique('units', 'name')->whereNull('archived_at')],
            'dimension' => ['required', Rule::enum(UnitDimension::class)],
            'factor_to_base' => self::factorRules(UnitDimension::tryFrom((string) $this->input('dimension'))),
            'position' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * The rules of `factor_to_base` for a unit of this dimension (shared with UpdateUnitRequest): a decimal string, more than
     * zero and at most 999 999 999 999, with up to 12 decimals, for every dimension but `package`; empty for `package`
     * (a roll or a cone holds a different amount per material). Without a valid dimension the dimension's own error is enough.
     *
     * @return list<string>
     */
    public static function factorRules(?UnitDimension $dimension): array
    {
        if ($dimension === UnitDimension::Package) {
            return ['prohibited'];
        }

        return [
            $dimension === null ? 'nullable' : 'required',
            'decimal:0,12',
            'gt:0',
            'max:999999999999',
        ];
    }
}
