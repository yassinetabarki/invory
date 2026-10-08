<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

class StoreRestockRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): the restock is a preview write, so it needs the preview ability (denied in production) on top of
     * the right to update the material. S-16-BE drops the preview ability.
     */
    public function authorize(): bool
    {
        $material = $this->route('material');
        $user = $this->user();

        return $material instanceof Material
            && $user !== null
            && $user->can('access-preview')
            && $user->can('update', $material);
    }

    /**
     * Decimals fit the columns they will end up in: `quantity_on_hand` is `decimal(14,4)`, `unit_cost` is `decimal(12,4)`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'unit_price' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'received_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:64'],
            'update_unit_cost' => ['required', 'boolean'],
        ];
    }
}
