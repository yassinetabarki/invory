<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\MaterialStockMode;
use App\Enums\StockStatus;
use App\Models\Material;
use App\Models\Product;
use App\Services\Costing\ProductCosting;
use App\Services\Products\ProductSizes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Capacity`: how many units of the product can be made now and what limits it. Built from a product that carries its `costing`
 * and its composition (the bottleneck is one of the materials of the lines).
 *
 * `max_producible` and `bottleneck` are the guaranteed figure (any mix of colours and sizes) and its limit. `best` is the most one colour and
 * size can make on its own (the same figure when the product has no By colour and size line). `by_variant` says where each is reached and
 * is null without a By colour and size line, and when no colour and size has a composition (INV-16, ADR-0027). A varying product carries its
 * colours (the costing loads them) and its sizes are read once per request, so none of this queries.
 *
 * @mixin Product
 */
final class CapacityResource extends JsonResource
{
    /** @return array{max_producible: int|null, bottleneck: array<string, mixed>|null, best: int|null, by_variant: array<string, mixed>|null, bottleneck_colour: array<string, mixed>|null, bottleneck_missing: bool, bottleneck_stock: string|null, bottleneck_colour_status: string|null} */
    public function toArray(Request $request): array
    {
        $costing = $this->costing;
        $capacity = $costing->result->capacity;
        $materials = $this->components->mapWithKeys(static fn ($component): array => [$component->material_id => $component->material]);
        $bottleneck = $capacity->bottleneckMaterialId === null ? null : $materials->get($capacity->bottleneckMaterialId);
        $colourId = $costing->variants?->bindingBottleneckColourId;
        $colour = $colourId === null ? null : $this->colours->firstWhere('id', $colourId);

        return [
            'max_producible' => $capacity->maxProducible,
            'bottleneck' => $bottleneck === null ? null : (new MaterialRefResource($bottleneck))->resolve($request),
            'best' => $costing->best(),
            'by_variant' => $this->byVariant($request, $costing, $materials->all()),
            // INV-21: the colour of the stock that limits the guaranteed figure (a material kept per colour), whether the material lacks
            // that colour, and the stock it limits on ("Denim 12oz, Black stock: 40 m"). Null/false for a one-stock bottleneck.
            'bottleneck_colour' => $colour === null ? null : (new ColourResource($colour))->resolve($request),
            'bottleneck_missing' => $costing->variants->bindingBottleneckMissing ?? false,
            'bottleneck_stock' => $costing->bottleneckStock(),
            // The status of the bottleneck's stock in that colour (low or out; out when the material lacks the colour): the callout adds "below
            // its reorder level" only when this colour is low. Null for a one-stock bottleneck.
            'bottleneck_colour_status' => $this->bottleneckColourStatus($bottleneck, $colourId),
        ];
    }

    /** The status of the bottleneck material's stock in colour `$colourId`; null unless the bottleneck keeps its stock per colour. */
    private function bottleneckColourStatus(?Material $bottleneck, ?int $colourId): ?string
    {
        if ($bottleneck === null || $colourId === null || $bottleneck->stock_mode !== MaterialStockMode::PerColour) {
            return null;
        }

        $row = $bottleneck->colours->firstWhere('colour_id', $colourId);

        return ($row === null ? StockStatus::Out : $bottleneck->colourStatus($row))->value;
    }

    /**
     * @param  array<int, Material>  $materials  by id
     * @return array{binding: array<string, mixed>, best_at: array<string, mixed>}|null
     */
    private function byVariant(Request $request, ProductCosting $costing, array $materials): ?array
    {
        $variants = $costing->variants;

        if (! $costing->perVariant() || $variants === null || $variants->bindingColourId === null || $variants->bestColourId === null) {
            return null;
        }

        $binding = $this->colours->firstWhere('id', $variants->bindingColourId);
        $best = $this->colours->firstWhere('id', $variants->bestColourId);
        $bestMaterial = $variants->bestMaterialId === null ? null : ($materials[$variants->bestMaterialId] ?? null);

        if ($binding === null || $best === null || $bestMaterial === null) {
            return null;
        }

        $sizes = app(ProductSizes::class)->of($this->resource);
        $size = $sizes->oneSize || $variants->bindingSizeId === null ? null : $sizes->sizes->firstWhere('id', $variants->bindingSizeId);

        return [
            'binding' => [
                'colour' => (new ColourResource($binding))->resolve($request),
                'size' => $size === null ? null : ['id' => $size->id, 'label' => $size->label],
            ],
            'best_at' => [
                'colour' => (new ColourResource($best))->resolve($request),
                'bottleneck' => (new MaterialRefResource($bestMaterial))->resolve($request),
            ],
        ];
    }
}
