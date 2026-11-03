<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\CuttingCalculator;
use App\Models\Material;
use App\Models\Product;
use App\Services\Costing\ProductCostingService;

/**
 * What cutting N units of a product takes from its materials (S-53, ADR-0014 section 8), worked out by the server so the screen never
 * computes. Start batch (INV-5) reads it without locks for its window and with locks for the batch itself, so the two can't disagree.
 * Since INV-14 orders no longer cut (ADR-0026).
 */
final readonly class CuttingPreview
{
    public function __construct(
        private ProductCostingService $costing,
        private CuttingCalculator $calculator,
    ) {}

    /**
     * What cutting `$units` units of the product takes from its composition's materials, on their stock as it is read now. With
     * `$lock` the materials are read with `FOR UPDATE`, in ascending id (ADR-0014 section 7), so the plan is the truth until the
     * transaction ends: only the move itself asks for that, inside its transaction.
     *
     * @return array{plan: CutPlan, materials: array<int, Material>} the materials keyed by id, as the plan was made from them
     */
    public function plan(Product $product, int $units, bool $lock = false): array
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

        return [
            'plan' => $this->calculator->take($this->costing->bomLines($product), $units),
            'materials' => $materials->all(),
        ];
    }
}
