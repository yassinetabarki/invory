<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `FinishedStock` (S-56): every size of the product's list, in order, 0 included, and their sum.
 *
 * @mixin FinishedStockLevel
 */
final class FinishedStockResource extends JsonResource
{
    /** @return array{units: int, sizes: list<array{size: array{id: int, label: string}, units: int}>} */
    public function toArray(Request $request): array
    {
        return [
            'units' => $this->units,
            'sizes' => array_map(fn (array $row): array => [
                'size' => ['id' => $row['size']->id, 'label' => $row['size']->label],
                'units' => $row['units'],
            ], $this->sizes),
        ];
    }
}
