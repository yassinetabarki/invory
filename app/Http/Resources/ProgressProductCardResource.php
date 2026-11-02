<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProgressProductCard` in resources/js/types/domain.ts (INV-5-f, AC-3): one product's card on Progress, "All phases". Built from
 * `ProgressBook::products()`; it only renders.
 *
 * @property array{product: Product, units: int, batches: int, one_size: bool, rows: list<array{colour: Colour, size: Size|null, by_phase: list<int>, in_production: int, on_shelf: int|null}>, total: array{by_phase: list<int>, in_production: int}} $resource
 */
final class ProgressProductCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'product' => (new RestockProductRefResource($this->resource['product']))->resolve($request),
            'units' => $this->resource['units'],
            'batches' => $this->resource['batches'],
            'one_size' => $this->resource['one_size'],
            'rows' => array_map(static fn (array $row): array => [
                'colour' => (new ColourResource($row['colour']))->resolve($request),
                'size' => $row['size'] === null ? null : ['id' => $row['size']->id, 'label' => $row['size']->label],
                'by_phase' => $row['by_phase'],
                'in_production' => $row['in_production'],
                'on_shelf' => $row['on_shelf'],
            ], $this->resource['rows']),
            'total' => $this->resource['total'],
        ];
    }
}
