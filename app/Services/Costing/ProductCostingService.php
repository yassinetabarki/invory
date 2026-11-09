<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\MarginCalculator;
use App\Domain\Costing\ProductionCapacity;
use App\Domain\Costing\VariantCosting;
use App\Domain\Costing\VariantCostingResult;
use App\Domain\Costing\VariantLine;
use App\Domain\Stock\VariantUnits;
use App\Enums\ComponentQuantityMode;
use App\Models\Product;
use App\Models\ProductComponentQuantity;
use App\Services\Products\ProductSizes;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/**
 * The Eloquent -> domain adapter: maps a Product with its composition loaded into the pure `App\Domain\Costing` inputs and
 * returns what the domain computes. It holds no maths of its own (ADR-0004), it only moves values, as decimal strings.
 *
 * It costs what it is given: a soft-deleted material on a BOM (possible on a deleted product) is costed like any other.
 * Whether a material may be on a product's BOM is validated when the BOM is written.
 *
 * A product with no "By colour and size" line (INV-16, ADR-0027) is costed once, as before ({@see ProductCosting::$variants} is null).
 * A product with at least one is costed per colour and size by {@see VariantCosting}, over its own colours and sizes.
 */
final readonly class ProductCostingService
{
    public function __construct(
        private CostingCalculator $calculator,
        private MarginCalculator $marginCalculator,
        private VariantCosting $variantCosting,
        private ProductSizes $sizes,
    ) {}

    /**
     * Cost, capacity and margin of one product.
     *
     * Only a product with a by-colour-and-size line reads more than its composition: its cells are read here as plain rows and its
     * colours are loaded when they are not yet (two queries), and its sizes come from {@see ProductSizes}.
     *
     * @param  Product  $product  with `components.material` loaded (the material's `unit` is always loaded with it)
     *
     * @throws LogicException If the composition or a line's material is not loaded: this class never queries for those on its own.
     */
    public function cost(Product $product): ProductCosting
    {
        if (! $this->varies($product)) {
            return $this->costSame($product);
        }

        return $this->costVariants($product, $this->loadVariantInputs([$product]));
    }

    /**
     * Cost, capacity and margin of many products with a constant number of queries: the compositions of every product that
     * does not have them yet are loaded in one go (components, their materials, and the categories and units of those), and for the
     * products that have a by-colour-and-size line (only those) their cells, colours and sizes, one query each for the whole lot.
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

        $varying = [];

        foreach ($collection as $product) {
            if ($this->varies($product)) {
                $varying[] = $product;
            }
        }

        $cells = $this->loadVariantInputs($varying);

        $costings = [];

        foreach ($collection as $product) {
            $costings[$product->id] = $this->varies($product) ? $this->costVariants($product, $cells) : $this->costSame($product);
        }

        return $costings;
    }

    /**
     * What limits the product once `$materialId` is restocked without limit ("next limit"), when that material is the current
     * bottleneck. Null otherwise, and null when the material is the only one of the composition.
     *
     * A product that varies by colour and size has none: its bottleneck is the binding variant's, and what limits it once restocked
     * is a per-variant question that no screen asks yet.
     */
    public function nextLimit(ProductCosting $costing, int $materialId): ?ProductionCapacity
    {
        if ($costing->varies() || $costing->result->capacity->bottleneckMaterialId !== $materialId) {
            return null;
        }

        $next = $this->calculator->capacityWithout($costing->bom, $materialId);

        return $next->maxProducible === null ? null : $next;
    }

    /**
     * The composition of each colour and size of a Start batch grid, in composition order, as the cutting maths takes it (INV-16-k):
     * every `same` line, and every by-colour-and-size line that has a cell for that colour and size, at that cell's quantity. A product
     * with no such line gives every variant the whole composition and reads no cell.
     *
     * @param  Product  $product  with `components.material` loaded
     * @param  array<int, array<int, int>>  $grid  units by colour id, then size id; only the variants to cut (units above 0), in the order to list them
     * @return list<VariantUnits>
     *
     * @throws LogicException If the composition or a line's material is not loaded.
     */
    public function variantCompositions(Product $product, array $grid): array
    {
        $lines = $this->varies($product) ? $this->variantLines($product, $this->loadCells([$product])) : null;
        $whole = $lines === null ? $this->bomLines($product) : [];
        $variants = [];

        foreach ($grid as $colourId => $bySize) {
            foreach ($bySize as $sizeId => $units) {
                $bom = $whole;

                foreach ($lines ?? [] as $line) {
                    $quantity = $line->quantityFor($colourId, $sizeId);

                    if ($quantity !== null) {
                        $bom[] = $line->bomLine($quantity);
                    }
                }

                $variants[] = new VariantUnits($colourId, $sizeId, $units, $bom);
            }
        }

        return $variants;
    }

    /**
     * Whether some line of the product is by colour and size.
     *
     * @throws LogicException If the composition is not loaded.
     */
    public function varies(Product $product): bool
    {
        if (! $product->relationLoaded('components')) {
            throw new LogicException("Product #{$product->id}: load `components.material` before costing it.");
        }

        foreach ($product->components as $component) {
            if ($component->quantity_mode === ComponentQuantityMode::ByVariant) {
                return true;
            }
        }

        return false;
    }

    /**
     * The composition as domain input, in display order, for a product whose lines all have one quantity.
     *
     * A by-colour-and-size line has no single quantity, so this refuses it: costing never comes here for such a product, it goes
     * through {@see VariantCosting}, and the cutting plan through {@see variantCompositions()}.
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

            if ($component->quantity === null) {
                throw new LogicException("Product #{$product->id}: the line of material #{$component->material_id} is by colour and size, which this reading of the composition doesn't support.");
            }

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

    /** The fast path: no by-colour-and-size line, one costing of the whole composition. */
    private function costSame(Product $product): ProductCosting
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
     * The product costed per colour and size. `result` and `margin` are the binding variant's (the one that makes the fewest), the
     * interim single figures until cost and margin are sent as ranges; `bom` is that variant's composition.
     *
     * @param  array<int, array<int, array<int, string>>>  $cells  the quantity by line id, colour id, then size id
     */
    private function costVariants(Product $product, array $cells): ProductCosting
    {
        $lines = $this->variantLines($product, $cells);
        $variants = $this->variantCosting->calculate(
            $lines,
            $this->variantsOf($product),
            $product->labor_cost,
            $product->overhead_cost,
            $product->selling_price,
        );

        return new ProductCosting(
            productId: $product->id,
            bom: $this->bindingBom($lines, $variants),
            result: $variants->single,
            margin: $this->marginCalculator->calculate($product->selling_price, $variants->single->cost->unitCost),
            variants: $variants,
        );
    }

    /**
     * Loads what costing per colour and size reads and is not loaded yet, for these products and no others: the cells of their
     * by-colour-and-size lines and their colours (one query each), and their sizes through {@see ProductSizes} (one query).
     *
     * The cells are read as plain rows (a product at the cap has 2,000), not hydrated as models, through the model's own query so that
     * the workshop scope applies.
     *
     * @param  list<Product>  $products  each with a by-colour-and-size line
     * @return array<int, array<int, array<int, string>>> the quantity by line (component) id, colour id, then size id
     */
    private function loadVariantInputs(array $products): array
    {
        if ($products === []) {
            return [];
        }

        (new Collection($products))->loadMissing('colours');

        $cells = $this->loadCells($products);

        // Primes the per-request cache: `variantsOf()` then asks for one product and queries nothing.
        $this->sizes->forProducts($products);

        return $cells;
    }

    /**
     * The cells of these products' by-colour-and-size lines, as plain rows.
     *
     * @param  list<Product>  $products  each with its components loaded
     * @return array<int, array<int, array<int, string>>> the quantity by line (component) id, colour id, then size id
     */
    private function loadCells(array $products): array
    {
        $lineIds = [];

        foreach ($products as $product) {
            foreach ($product->components as $component) {
                if ($component->quantity_mode === ComponentQuantityMode::ByVariant) {
                    $lineIds[] = $component->id;
                }
            }
        }

        $cells = [];

        foreach (ProductComponentQuantity::query()->whereIn('product_component_id', $lineIds)->toBase()->get(['product_component_id', 'colour_id', 'size_id', 'quantity']) as $row) {
            // SQLite hands a decimal column back as a number, PostgreSQL as a string: either reads as the plain decimal.
            $cells[(int) $row->product_component_id][(int) $row->colour_id][(int) $row->size_id] = (string) $row->quantity;
        }

        return $cells;
    }

    /**
     * The composition as the domain takes it. A by-colour-and-size line with no cell is used by no variant, so it is left out.
     *
     * @param  array<int, array<int, array<int, string>>>  $cells  the quantity by line id, colour id, then size id
     * @return list<VariantLine>
     *
     * @throws LogicException
     */
    private function variantLines(Product $product, array $cells): array
    {
        $lines = [];

        foreach ($product->components as $component) {
            if (! $component->relationLoaded('material')) {
                throw new LogicException("Product #{$product->id}: load `components.material` before costing it.");
            }

            $material = $component->material;
            $quantity = $component->quantity;
            $lineCells = null;

            if ($component->quantity_mode === ComponentQuantityMode::ByVariant) {
                $lineCells = $cells[$component->id] ?? [];

                if ($lineCells === []) {
                    continue;
                }

                $quantity = null;
            }

            $lines[] = new VariantLine(
                materialId: $material->id,
                materialName: $material->name,
                unit: $material->unit->code,
                quantity: $quantity,
                cells: $lineCells,
                wastePercent: $component->waste_percent,
                unitCost: $material->unit_cost,
                stockOnHand: $material->quantity_on_hand,
            );
        }

        return $lines;
    }

    /**
     * The product's colours (list order) times its own sizes (their order).
     *
     * @return list<array{0: int, 1: int}>
     *
     * @throws LogicException
     */
    private function variantsOf(Product $product): array
    {
        if (! $product->relationLoaded('colours')) {
            throw new LogicException("Product #{$product->id}: load `colours` before costing it.");
        }

        $sizeIds = $this->sizes->of($product)->ids();
        $variants = [];

        foreach ($product->colours as $colour) {
            foreach ($sizeIds as $sizeId) {
                $variants[] = [$colour->id, $sizeId];
            }
        }

        if ($variants === []) {
            throw new LogicException("Product #{$product->id}: it needs at least one colour and one size to be costed by colour and size.");
        }

        return $variants;
    }

    /**
     * The composition of the binding variant (the one that makes the fewest; the first variant when none has a composition).
     *
     * @param  list<VariantLine>  $lines
     * @return list<BomLine>
     */
    private function bindingBom(array $lines, VariantCostingResult $variants): array
    {
        $colourId = $variants->bindingColourId ?? $variants->variants[0]->colourId;
        $sizeId = $variants->bindingSizeId ?? $variants->variants[0]->sizeId;
        $bom = [];

        foreach ($lines as $line) {
            $quantity = $line->quantityFor($colourId, $sizeId);

            if ($quantity !== null) {
                $bom[] = $line->bomLine($quantity);
            }
        }

        return $bom;
    }
}
