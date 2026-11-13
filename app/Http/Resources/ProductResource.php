<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Costing\LineCost;
use App\Domain\Sizes\SizeLine;
use App\Enums\ComponentQuantityMode;
use App\Enums\MaterialStockMode;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\Size;
use App\Services\Costing\ProductCosting;
use App\Services\Products\ProductSizes;
use App\Services\Products\ProductSizeSet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * `Product`: the detail of one product with its cost breakdown, margin, capacity and composition. The product must carry its
 * category, its composition (with the materials), its colours and the attribute the
 * controller sets (`costing`).
 *
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $costing = $this->costing;
        $sizes = app(ProductSizes::class);
        $set = $sizes->of($this->resource);
        $labels = array_values($set->sizes->map(fn (Size $size): string => $size->label)->all());
        // The position of each colour and each size in the product's lists: the order of the cells of a By colour and size line.
        $cellOrder = ['colours' => $this->colours->pluck('id')->flip()->all(), 'sizes' => $set->sizes->pluck('id')->flip()->all()];

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category->code,
            'category_label' => $this->category->name,
            'sizes' => array_values($set->sizes->map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->label])->all()),
            'one_size' => $set->oneSize,
            'sizes_line' => SizeLine::badge(
                $labels,
                fn (): array => array_values($sizes->workshop()->map(fn (Size $size): string => $size->label)->all()),
                $set->oneSize,
            ),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'labor_cost' => $this->labor_cost,
            'overhead_cost' => $this->overhead_cost,
            'selling_price' => $this->selling_price,
            'cost' => (new CostBreakdownResource($this->resource))->resolve($request),
            'margin' => $costing->marginRange(),
            'capacity' => (new CapacityResource($this->resource))->resolve($request),
            'capacity_by_colour' => $this->capacityByColour($request, $costing, $set),
            'colours' => ColourResource::collection($this->colours)->resolve($request),
            'components' => $this->components
                ->values()
                ->map(fn (ProductComponent $component, int $index): array => $this->line($request, $component, $index, $costing, $cellOrder))
                ->all(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * What each colour can make, in the product's colour order; null unless a line is By colour and size or a material is kept per colour
     * (INV-21). A colour that uses no material
     * comes back with every figure null ("No composition"). The colours are the product's own (the costing loads them), the binding size
     * is one of its sizes (null for a "One size" product) and the bottleneck is a material of its lines: nothing here queries.
     *
     * @return list<array<string, mixed>>|null
     */
    private function capacityByColour(Request $request, ProductCosting $costing, ProductSizeSet $set): ?array
    {
        $variants = $costing->variants;

        if ($variants === null || ! $costing->perVariant()) {
            return null;
        }

        $byColour = [];

        foreach ($variants->colours as $capacity) {
            $byColour[$capacity->colourId] = $capacity;
        }

        $materials = $this->components->mapWithKeys(static fn (ProductComponent $component): array => [$component->material_id => $component->material]);
        $rows = [];

        foreach ($this->colours as $colour) {
            $capacity = $byColour[$colour->id] ?? null;

            if ($capacity === null) {
                continue;
            }

            $size = $set->oneSize || $capacity->bindingSizeId === null ? null : $set->sizes->firstWhere('id', $capacity->bindingSizeId);
            $material = $capacity->bottleneckMaterialId === null ? null : $materials->get($capacity->bottleneckMaterialId);

            $rows[] = (new ColourCapacityResource($capacity, $colour, $size, $material))->resolve($request);
        }

        return $rows;
    }

    /**
     * One line of the composition. A product that varies by colour and size is costed per variant, and the costing the product carries
     * is its binding variant's, which holds the `same` lines and only the By colour and size lines that variant uses: a `same` line is
     * found there by its material (its figures are the same in every variant), and a By colour and size line has no figures of its own.
     *
     * @param  array{colours: array<int, int>, sizes: array<int, int>}  $cellOrder
     * @return array<string, mixed>
     */
    private function line(Request $request, ProductComponent $component, int $index, ProductCosting $costing, array $cellOrder): array
    {
        if ($component->quantity_mode === ComponentQuantityMode::ByVariant) {
            $line = null;
            $isBottleneck = $costing->result->capacity->bottleneckMaterialId === $component->material_id;
        } else {
            $line = collect($costing->result->cost->lines)->first(fn (LineCost $cost): bool => $cost->materialId === $component->material_id)
                ?? throw new LogicException('The costing has a line for every Same for every variant component.');
            $isBottleneck = $line->isBottleneck;
        }

        return (new ProductComponentResource($component, $line, $index + 1, $isBottleneck, $this->cells($component, $cellOrder), $this->stockByColour($request, $component, $costing)))->resolve($request);
    }

    /**
     * What a material kept per colour holds for each of the product's colours that uses the line, in the product's colour order: that
     * colour's stock and the lowest and highest units it covers over the colour's sizes. A colour no size of which uses the line has no
     * reading. Null for a one-stock material. The colour rows come with the costing, which loads them.
     *
     * @return list<array<string, mixed>>|null
     */
    private function stockByColour(Request $request, ProductComponent $component, ProductCosting $costing): ?array
    {
        $material = $component->material;

        if ($material->stock_mode !== MaterialStockMode::PerColour) {
            return null;
        }

        $span = $costing->variants?->materials[$material->id] ?? null;
        $rows = $material->colours->keyBy('colour_id');
        $readings = [];

        foreach ($this->colours as $colour) {
            $units = $span->byColour[$colour->id] ?? null;

            if ($units === null) {
                continue;
            }

            $readings[] = [
                'colour' => (new ColourResource($colour))->resolve($request),
                'missing' => $units['missing'],
                'stock_on_hand' => $rows->get($colour->id)->quantity_on_hand ?? '0.0000',
                'units_possible' => ['min' => $units['min'], 'max' => $units['max']],
            ];
        }

        return $readings;
    }

    /**
     * The cells of a By colour and size line, colours in the product's list order, then sizes in theirs. A `same` line has none (and
     * its cells are not loaded unless the product has a By colour and size line).
     *
     * @param  array{colours: array<int, int>, sizes: array<int, int>}  $cellOrder
     * @return list<array{colour_id: int, size_id: int, quantity: string}>
     */
    private function cells(ProductComponent $component, array $cellOrder): array
    {
        if ($component->quantity_mode !== ComponentQuantityMode::ByVariant) {
            return [];
        }

        $position = fn (ProductComponentQuantity $cell): array => [$cellOrder['colours'][$cell->colour_id] ?? PHP_INT_MAX, $cellOrder['sizes'][$cell->size_id] ?? PHP_INT_MAX];

        return array_values($component->quantities
            ->sort(fn (ProductComponentQuantity $a, ProductComponentQuantity $b): int => $position($a) <=> $position($b))
            ->map(fn (ProductComponentQuantity $cell): array => ['colour_id' => $cell->colour_id, 'size_id' => $cell->size_id, 'quantity' => $cell->quantity])
            ->all());
    }
}
