<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Validation\WorkshopRule;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): Response
    {
        $material = $this->route('material');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $material instanceof Material ? Gate::inspect('update', $material) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $material = $this->route('material');

        return StoreMaterialRequest::fieldRules(
            WorkshopRule::unique(Material::class, 'sku')->ignore($material),
            // An archived category or unit stays valid on the material that already has it.
            currentCategory: $material instanceof Material ? $material->category->code : null,
            currentUnit: $material instanceof Material ? $material->unit->code : null,
        );
    }

    /**
     * Q7 (ADR-0008): a material's unit can't change while a product's composition uses it, because every quantity of that
     * composition would silently change meaning. Keeping the unit, or changing anything else, is always allowed.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $material = $this->route('material');
            $unit = $this->input('unit');

            if (! $material instanceof Material || ! is_string($unit) || $validator->errors()->has('unit') || $unit === $material->unit->code) {
                return;
            }

            $compositions = $this->compositionsUsing($material);

            if ($compositions > 0) {
                $validator->errors()->add('unit', trans_choice(
                    'This material is used in :count product composition, so its unit can\'t change.|This material is used in :count product compositions, so its unit can\'t change.',
                    $compositions,
                ));
            }
        }];
    }

    /**
     * How many non-deleted products' compositions use this material. The lines of a soft-deleted product stay for history and
     * don't lock the unit.
     */
    private function compositionsUsing(Material $material): int
    {
        return ProductComponent::query()
            ->where('material_id', $material->id)
            ->whereIn('product_id', Product::query()->select('id'))
            ->count();
    }
}
