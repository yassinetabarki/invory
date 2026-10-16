<?php

declare(strict_types=1);

namespace App\Services\Costing;

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
 * products there are) and never the others.
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
                unitsCovered: $requirement->unitsPossible,
                maxProducible: $capacity->maxProducible,
                bottleneck: $bottleneck,
                isBottleneck: $requirement->isBottleneck,
                nextLimit: $next?->maxProducible === null || $limiting === null ? null : ['material' => $limiting, 'max_producible' => $next->maxProducible],
            );
        }

        // The products are already by name: a stable sort moves the bottleneck rows up and keeps that order inside each group.
        usort($usages, static fn (MaterialUsage $a, MaterialUsage $b): int => $b->isBottleneck <=> $a->isBottleneck);

        return $usages;
    }
}
