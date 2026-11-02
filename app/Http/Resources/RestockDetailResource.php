<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `RestockDetail` in resources/js/types/domain.ts (INV-3, AC-5): the restock panel. Built from `RestockBook::detail()`. Times are the
 * workshop's wall-clock time, "2026-10-25T14:32", so the screen shows them as they were, whatever the browser's timezone.
 *
 * @property array{restock: ProductRestock, products: list<array{product: Product, units: int}>, blocks: list<array{product: Product, sizes: list<Size>, one_size: bool, units: int, rows: list<array{colour: Colour, units: list<int>, total: int}>, totals: list<int>|null}>, undo_refusal: string|null} $resource
 */
final class RestockDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $restock = $this->resource['restock'];

        return [
            ...RestockSummaryResource::summary($restock, $this->resource['products']),
            'note' => $restock->note,
            'recorded_at' => $restock->created_at->format('Y-m-d\TH:i'),
            'products_count' => count($this->resource['products']),
            'undone_at' => $restock->undone_at?->format('Y-m-d\TH:i'),
            'undone_by' => $restock->undoer?->name,
            'blocks' => array_map(fn (array $block): array => [
                'product' => (new RestockProductRefResource($block['product']))->toArray($request),
                'sizes' => array_map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->label], $block['sizes']),
                'one_size' => $block['one_size'],
                'units' => $block['units'],
                'rows' => array_map(fn (array $row): array => [
                    'colour' => ['id' => $row['colour']->id, 'code' => $row['colour']->code, 'name' => $row['colour']->name, 'swatch' => $row['colour']->swatch],
                    'units' => $row['units'],
                    'total' => $row['total'],
                ], $block['rows']),
                'totals' => $block['totals'],
            ], $this->resource['blocks']),
            'undo_refusal' => $this->resource['undo_refusal'],
        ];
    }
}
