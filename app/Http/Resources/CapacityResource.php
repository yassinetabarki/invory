<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Capacity`: how many units of the product can be made now and what limits it. Built from a product that carries its `costing`
 * and its composition (the bottleneck is one of the materials of the lines).
 *
 * @mixin Product
 */
final class CapacityResource extends JsonResource
{
    /** @return array{max_producible: int|null, bottleneck: array<string, mixed>|null} */
    public function toArray(Request $request): array
    {
        $capacity = $this->costing->result->capacity;
        $bottleneck = null;

        if ($capacity->bottleneckMaterialId !== null) {
            foreach ($this->components as $component) {
                if ($component->material_id === $capacity->bottleneckMaterialId) {
                    $bottleneck = (new MaterialRefResource($component->material))->resolve($request);

                    break;
                }
            }
        }

        return [
            'max_producible' => $capacity->maxProducible,
            'bottleneck' => $bottleneck,
        ];
    }
}
