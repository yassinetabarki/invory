<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreRestockRequest extends FormRequest
{
    /** Books a receipt of stock: the right to update the material (MaterialPolicy). */
    public function authorize(): Response
    {
        $material = $this->route('material');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $material instanceof Material ? Gate::inspect('update', $material) : Response::denyAsNotFound();
    }

    /**
     * `update_unit_cost` is optional and means true when omitted (F-20, costing skill "Restock"). A value that is sent must still be
     * a boolean, so a typo can't silently keep the old cost.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('update_unit_cost')) {
            $this->merge(['update_unit_cost' => true]);
        }
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
