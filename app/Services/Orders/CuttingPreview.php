<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\MaterialTake;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Services\Costing\ProductCostingService;

/**
 * What cutting a grid of colours and sizes of a product takes from its materials (S-53, ADR-0014 section 8; per colour and size since
 * INV-16, ADR-0027 decision 6), worked out by the server so the screen never computes. Start batch (INV-5) reads it without locks for
 * its window and with locks for the batch itself, so the two can't disagree. Since INV-14 orders no longer cut (ADR-0026).
 */
final readonly class CuttingPreview
{
    public function __construct(
        private ProductCostingService $costing,
        private CuttingCalculator $calculator,
    ) {}

    /**
     * What cutting the grid takes from the composition's materials, on their stock as it is read now. With `$lock` the materials are
     * read with `FOR UPDATE`, in ascending id (ADR-0014 section 7), then the cells of the composition, so the plan is the truth until
     * the transaction ends: only Start batch asks for that, inside its transaction, after it locked the product.
     *
     * @param  array<int, array<int, int>>  $grid  units by colour id, then size id; only the variants to cut (units above 0), in the order to write them
     * @return array{plan: CutPlan, materials: array<int, Material>, colours: array<int, Colour>} the materials keyed by id, as the plan was made from them, and the colours of its per-colour stocks keyed by id (one query; the window's rows only serialize)
     */
    public function plan(Product $product, array $grid, bool $lock = false): array
    {
        $components = $product->components()->get();
        $ids = $components->pluck('material_id')->all();

        // A soft-deleted material stays readable (a deleted product's composition may still point at it), like in costing.
        $materials = $ids === [] ? collect() : Material::query()
            ->withTrashed()->without('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        foreach ($components as $component) {
            $component->setRelation('material', $materials->get($component->material_id));
        }

        $product->setRelation('components', $components);

        $plan = $this->calculator->takeVariants(
            $this->costing->variantCompositions($product, $grid),
            array_values(array_unique(array_map(intval(...), $ids))),
            // A by-colour-and-size line or a per-colour material: the variants differ (ADR-0029 section 6).
            $this->costing->costsPerVariant($product),
        );
        $colourIds = array_values(array_unique(array_filter(array_map(static fn (MaterialTake $take): ?int => $take->colourId, $plan->takes))));

        return [
            'plan' => $plan,
            'materials' => $materials->all(),
            'colours' => $colourIds === [] ? [] : Colour::query()->whereIn('id', $colourIds)->get()->keyBy('id')->all(),
        ];
    }
}
