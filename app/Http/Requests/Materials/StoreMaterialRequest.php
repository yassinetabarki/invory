<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use App\Services\Reference\ReferenceCode;
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
     * `category` and `unit` are codes of non-archived rows (ADR-0008). On update, the material's own current code stays
     * valid even when it has been archived since.
     *
     * @return array<string, mixed>
     */
    public static function fieldRules(Unique $uniqueSku, ?string $currentCategory = null, ?string $currentUnit = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', $uniqueSku],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('material_categories', $currentCategory)],
            'unit' => ['required', 'string', ReferenceCode::active('units', $currentUnit)],
            'unit_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'quantity_on_hand' => ['required', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'reorder_level' => ['nullable', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'supplier_reference' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:64'],
            'composition' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            // Not columns: the Actions hand `photo` and `remove_photo` to PhotoStorage, which stores or removes the file.
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
