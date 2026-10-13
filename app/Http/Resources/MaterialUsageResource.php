<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Costing\MaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialUsage` in resources/js/types/domain.ts. The product's `photo_url` is the model accessor over `photo_path`.
 *
 * @mixin MaterialUsage
 */
final class MaterialUsageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'product' => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'photo_url' => $this->product->photo_url,
                'status' => $this->product->status->value,
                'status_label' => $this->product->status->label(),
            ],
            'effective_quantity' => $this->effectiveQuantity,
            'units_covered' => $this->unitsCovered,
            'max_producible' => $this->maxProducible,
            'bottleneck' => (new MaterialRefResource($this->bottleneck))->resolve($request),
            'is_bottleneck' => $this->isBottleneck,
            'next_limit' => $this->nextLimit === null ? null : [
                'material' => (new MaterialRefResource($this->nextLimit['material']))->resolve($request),
                'max_producible' => $this->nextLimit['max_producible'],
            ],
        ];
    }
}
