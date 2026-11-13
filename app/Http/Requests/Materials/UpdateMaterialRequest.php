<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Services\Materials\MaterialStockSentences;
use App\Validation\WorkshopRule;
use Brick\Math\BigDecimal;
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

    /** `stock_mode` and `cost_mode` are optional: left out, they are the material's own. */
    protected function prepareForValidation(): void
    {
        $material = $this->route('material');

        StoreMaterialRequest::defaultModes(
            $this,
            $material instanceof Material ? $material->stock_mode : MaterialStockMode::Single,
            $material instanceof Material ? $material->cost_mode : MaterialCostMode::Same,
        );
    }

    /**
     * Back to one stock (INV-21-g): the colours' total is the one stock, so `quantity_on_hand` is not asked, and `unit_cost` is the
     * cost to keep, required when the colours had a cost each (with the same cost the material's own stays).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $material = $this->route('material');

        $rules = StoreMaterialRequest::fieldRules(
            WorkshopRule::unique(Material::class, 'sku')->ignore($material),
            // An archived category or unit stays valid on the material that already has it.
            currentCategory: $material instanceof Material ? $material->category->code : null,
            currentUnit: $material instanceof Material ? $material->unit->code : null,
            stockMode: $this->string('stock_mode')->value(),
            costMode: $this->string('cost_mode')->value(),
        );

        if ($this->isJoin()) {
            unset($rules['quantity_on_hand']);

            if ($material instanceof Material && $material->cost_mode !== MaterialCostMode::PerColour) {
                $rules['unit_cost'] = ['nullable', ...array_slice($rules['unit_cost'], 1)];
            }
        }

        return $rules;
    }

    /**
     * Switching to a stock per colour with stock (INV-21-g): the first colour is the one that takes the stock you have now.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $material = $this->route('material');
        $messages = StoreMaterialRequest::fieldMessages();

        if ($material instanceof Material && $material->stock_mode === MaterialStockMode::Single && $this->input('stock_mode') === MaterialStockMode::PerColour->value && BigDecimal::of($material->quantity_on_hand)->isPositive()) {
            $messages['colours.0.colour_id.required'] = __('Choose the colour that holds the :quantity you have now.', [
                'quantity' => MaterialStockSentences::quantity($material->quantity_on_hand, $material->unit->code),
            ]);
        }

        return $messages;
    }

    /** A save of a per-colour material that submits one stock. */
    private function isJoin(): bool
    {
        $material = $this->route('material');

        return $material instanceof Material && $material->stock_mode === MaterialStockMode::PerColour && $this->input('stock_mode') === MaterialStockMode::Single->value;
    }

    /**
     * Q7 (ADR-0008): a material's unit can't change while a product's composition uses it, because every quantity of that
     * composition would silently change meaning. Keeping the unit, or changing anything else, is always allowed.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => StoreMaterialRequest::colourRows($this, $validator), function (Validator $validator): void {
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
