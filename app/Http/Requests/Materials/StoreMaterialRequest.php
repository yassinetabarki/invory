<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCategory;
use App\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Unique;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Material::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(Rule::unique('materials', 'sku')->withoutTrashed());
    }

    /**
     * Rules shared with UpdateMaterialRequest.
     *
     * @return array<string, mixed>
     */
    public static function fieldRules(Unique $uniqueSku): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', $uniqueSku],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(MaterialCategory::class)],
            'unit' => ['required', Rule::enum(UnitOfMeasure::class)],
            'unit_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'quantity_on_hand' => ['required', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'reorder_level' => ['nullable', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'supplier_reference' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:64'],
            'composition' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            // MOCK (ADR-0006): photos are validated for real but not stored until S-15-BE. The controllers drop both keys
            // before the material is saved (strict models throw on attributes that are not columns).
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
