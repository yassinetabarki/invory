<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Sizes\SizeLine;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Size;
use App\Services\Products\ProductSizes;
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
        $margin = $costing->margin;
        $sizes = app(ProductSizes::class);
        $set = $sizes->of($this->resource);
        $labels = array_values($set->sizes->map(fn (Size $size): string => $size->label)->all());

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
            'margin' => $margin === null ? null : ['amount' => $margin->amount, 'percent' => $margin->percent],
            'capacity' => (new CapacityResource($this->resource))->resolve($request),
            'colours' => ColourResource::collection($this->colours)->resolve($request),
            'components' => $this->components
                ->values()
                ->map(fn (ProductComponent $component, int $index): array => (new ProductComponentResource(
                    $component,
                    $costing->result->cost->lines[$index] ?? throw new LogicException('The costing has one line per component.'),
                    $index + 1,
                ))->resolve($request))
                ->all(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
