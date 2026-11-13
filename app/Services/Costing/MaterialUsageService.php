<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Domain\Costing\MaterialSpan;
use App\Enums\MaterialStockMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * "What you can make with it": for a material, every non-deleted product whose composition uses it, costed by
 * {@see ProductCostingService} (the maths is the domain's, see "Material usage and next limit" in the costing skill).
 *
 * It reads only the products that use the material (their compositions and the materials of those, 3 queries however many
 * products there are, plus 3 more (cells, colours, sizes) when any of them has a By colour and size line) and never the others.
 */
final readonly class MaterialUsageService
{
    public function __construct(private ProductCostingService $costing) {}

    /**
     * Bottleneck rows first, then the products by name (case-insensitive) and id.
     *
     * @param  Collection<int, Unit>  $units  every unit by id, so the materials of the compositions don't query their own
     * @return list<MaterialUsage>
     */
    public function for(Material $material, Collection $units): array
    {
        $products = Product::query()
            ->whereHas('components', fn (Builder $lines) => $lines->where('material_id', $material->id))
            ->with(['components.material' => fn ($materials) => $materials->without(['category', 'unit'])])
            ->byName()
            ->get();

        foreach ($products as $product) {
            foreach ($product->components as $component) {
                $component->material->setRelation('unit', $units->get($component->material->unit_id));
            }
        }

        $costings = $this->costing->costMany($products);
        $usages = [];

        foreach ($products as $product) {
            $costing = $costings[$product->id];
            $capacity = $costing->result->capacity;
            $materials = $product->components->mapWithKeys(static fn ($component): array => [$component->material_id => $component->material]);
            $bottleneck = $capacity->bottleneckMaterialId === null ? null : $materials->get($capacity->bottleneckMaterialId);

            if ($costing->perVariant()) {
                // A product costed per colour and size (a By colour and size line, or a material kept per colour): the guaranteed figure,
                // the binding variant's bottleneck and the next limit on its colour and size, and the lowest and highest need and units
                // covered over the variants that use the material (INV-16, INV-21). The need is one figure ("8.0000") when every such
                // variant needs the same, and null ("Varies") when it differs.
                $variants = $costing->variants;
                $span = $variants?->materials[$material->id] ?? null;

                if ($variants === null || $span === null || $variants->guaranteed === null || $bottleneck === null) {
                    continue;
                }

                // A material kept per colour: one reading per product colour (list order); the covered range leaves out the colours the
                // material lacks (the list says "no stock" for them, not 0).
                $byColour = $this->readings($span, $materials->get($material->id), $product);
                $present = array_filter($byColour ?? [], static fn (array $reading): bool => ! $reading['missing']);
                $isBottleneck = $variants->bindingMaterialId === $material->id;
                $bottleneckColour = $variants->bindingBottleneckColourId === null ? null : $product->colours->firstWhere('id', $variants->bindingBottleneckColourId);
                $next = $isBottleneck ? $this->costing->bindingNextLimit($costing, $material->id) : null;
                $limiting = $next?->bottleneckMaterialId === null ? null : $materials->get($next->bottleneckMaterialId);

                $usages[] = new MaterialUsage(
                    product: $product,
                    effectiveQuantity: $span->varies() ? null : $span->neededMin,
                    unitsCoveredMin: $present === [] ? $span->unitsCoveredMin : min(array_column(array_column($present, 'units_possible'), 'min')),
                    unitsCoveredMax: $present === [] ? $span->unitsCoveredMax : max(array_column(array_column($present, 'units_possible'), 'max')),
                    maxProducible: $variants->guaranteed,
                    bottleneck: $bottleneck,
                    isBottleneck: $isBottleneck,
                    nextLimit: $next?->maxProducible === null || $limiting === null ? null : ['material' => $limiting, 'max_producible' => $next->maxProducible],
                    productVaries: $costing->varies(),
                    byColour: $byColour,
                    bottleneckColour: $bottleneckColour,
                    limitColour: $isBottleneck ? $bottleneckColour : null,
                );

                continue;
            }

            // The domain sums every line of the material (F-41); read its total instead of picking a line.
            $requirement = null;

            foreach ($costing->result->requirements as $candidate) {
                if ($candidate->materialId === $material->id) {
                    $requirement = $candidate;
                    break;
                }
            }

            if ($requirement === null || $capacity->maxProducible === null || $bottleneck === null) {
                continue;
            }

            // Null unless this material is the bottleneck, and when it is the only material of the composition.
            $next = $this->costing->nextLimit($costing, $material->id);
            $limiting = $next?->bottleneckMaterialId === null ? null : $materials->get($next->bottleneckMaterialId);

            $usages[] = new MaterialUsage(
                product: $product,
                effectiveQuantity: $requirement->perUnit,
                unitsCoveredMin: $requirement->unitsPossible,
                unitsCoveredMax: $requirement->unitsPossible,
                maxProducible: $capacity->maxProducible,
                bottleneck: $bottleneck,
                isBottleneck: $requirement->isBottleneck,
                nextLimit: $next?->maxProducible === null || $limiting === null ? null : ['material' => $limiting, 'max_producible' => $next->maxProducible],
                productVaries: false,
            );
        }

        // The products are already by name: a stable sort moves the bottleneck rows up and keeps that order inside each group.
        usort($usages, static fn (MaterialUsage $a, MaterialUsage $b): int => $b->isBottleneck <=> $a->isBottleneck);

        return $usages;
    }

    /**
     * What a material kept per colour holds for each of the product's colours that uses it, in list order (null for a one-stock material).
     *
     * @return list<array{colour: Colour, missing: bool, stock_on_hand: string, units_possible: array{min: int, max: int}}>|null
     */
    private function readings(MaterialSpan $span, ?Material $material, Product $product): ?array
    {
        if ($material === null || $material->stock_mode !== MaterialStockMode::PerColour) {
            return null;
        }

        $rows = $material->colours->keyBy('colour_id');
        $readings = [];

        foreach ($product->colours as $colour) {
            $units = $span->byColour[$colour->id] ?? null;

            if ($units !== null) {
                $readings[] = [
                    'colour' => $colour,
                    'missing' => $units['missing'],
                    'stock_on_hand' => $rows->get($colour->id)->quantity_on_hand ?? '0.0000',
                    'units_possible' => ['min' => $units['min'], 'max' => $units['max']],
                ];
            }
        }

        return $readings;
    }
}
