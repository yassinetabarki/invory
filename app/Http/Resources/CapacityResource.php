<?php

declare(strict_types=1);

namespace App\Http\Resources;

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
    /** @return array{max_producible: int|null, bottleneck: array<string, mixed>|null, best: int|null, by_variant: array<string, mixed>|null} */
    public function toArray(Request $request): array
    {
        $costing = $this->costing;
        $capacity = $costing->result->capacity;
        $materials = $this->components->mapWithKeys(static fn ($component): array => [$component->material_id => $component->material]);
        $bottleneck = $capacity->bottleneckMaterialId === null ? null : $materials->get($capacity->bottleneckMaterialId);

        return [
            'max_producible' => $capacity->maxProducible,
            'bottleneck' => $bottleneck === null ? null : (new MaterialRefResource($bottleneck))->resolve($request),
            'best' => $costing->best(),
            'by_variant' => $this->byVariant($request, $costing, $materials->all()),
        ];
    }

    /**
     * @param  array<int, Material>  $materials  by id
     * @return array{binding: array<string, mixed>, best_at: array<string, mixed>}|null
     */
    private function byVariant(Request $request, ProductCosting $costing, array $materials): ?array
    {
        $variants = $costing->variants;

        if ($variants === null || ! $variants->varies || $variants->bindingColourId === null || $variants->bestColourId === null) {
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
