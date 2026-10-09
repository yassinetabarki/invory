<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\MarginCalculator;
use App\Domain\Costing\ProductionCapacity;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/**
 * The Eloquent -> domain adapter: maps a Product with its composition loaded into the pure `App\Domain\Costing` inputs and
 * returns what the domain computes. It holds no maths of its own (ADR-0004), it only moves values, as decimal strings.
 *
 * It costs what it is given: a soft-deleted material on a BOM (possible on a deleted product) is costed like any other.
 * Whether a material may be on a product's BOM is validated when the BOM is written.
 */
final readonly class ProductCostingService
{
    public function __construct(
        private CostingCalculator $calculator,
        private MarginCalculator $marginCalculator,
    ) {}

    /**
     * Cost, capacity and margin of one product.
     *
     * @param  Product  $product  with `components.material` loaded (the material's `unit` is always loaded with it)
     *
     * @throws LogicException If the composition or a line's material is not loaded: this class never queries on its own.
     */
    public function cost(Product $product): ProductCosting
    {
        $bom = $this->bomLines($product);
        $result = $this->calculator->calculate($bom, $product->labor_cost, $product->overhead_cost);

        return new ProductCosting(
            productId: $product->id,
            bom: $bom,
            result: $result,
            margin: $this->marginCalculator->calculate($product->selling_price, $result->cost->unitCost),
        );
    }

    /**
     * Cost, capacity and margin of many products with a constant number of queries: the compositions of every product that
     * does not have them yet are loaded in one go (components, their materials, and the categories and units of those).
     *
     * @param  iterable<Product>  $products
     * @return array<int, ProductCosting> keyed by product id
     */
    public function costMany(iterable $products): array
    {
        $collection = new Collection;

        foreach ($products as $product) {
            $collection->push($product);
        }

        $collection->loadMissing('components.material');

        $costings = [];

        foreach ($collection as $product) {
            $costings[$product->id] = $this->cost($product);
        }

        return $costings;
    }

    /**
     * What limits the product once `$materialId` is restocked without limit ("next limit"), when that material is the current
     * bottleneck. Null otherwise, and null when the material is the only one of the composition.
     */
    public function nextLimit(ProductCosting $costing, int $materialId): ?ProductionCapacity
    {
        if ($costing->result->capacity->bottleneckMaterialId !== $materialId) {
            return null;
        }

        $next = $this->calculator->capacityWithout($costing->bom, $materialId);

        return $next->maxProducible === null ? null : $next;
    }

    /**
     * The composition as domain input, in display order.
     *
     * @return list<BomLine>
     *
     * @throws LogicException
     */
    public function bomLines(Product $product): array
    {
        if (! $product->relationLoaded('components')) {
            throw new LogicException("Product #{$product->id}: load `components.material` before costing it.");
        }

        $bom = [];

        foreach ($product->components as $component) {
            if (! $component->relationLoaded('material')) {
                throw new LogicException("Product #{$product->id}: load `components.material` before costing it.");
            }

            $material = $component->material;

            $bom[] = new BomLine(
                materialId: $material->id,
                materialName: $material->name,
                unit: $material->unit->code,
                quantity: $component->quantity,
                wastePercent: $component->waste_percent,
                unitCost: $material->unit_cost,
                stockOnHand: $material->quantity_on_hand,
            );
        }

        return $bom;
    }
}
