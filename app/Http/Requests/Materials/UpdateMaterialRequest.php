<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $material instanceof Material
            && ($this->user()?->can('update', $material) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return StoreMaterialRequest::fieldRules(
            Rule::unique('materials', 'sku')->ignore($this->route('material'))->withoutTrashed(),
        );
    }
}
