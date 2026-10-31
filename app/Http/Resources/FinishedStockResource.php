<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Colour;
use App\Models\Size;
use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `FinishedStock` (S-56, INV-2): every size of the product's list, in order, 0 included, and their sum, over every colour; `colours` is
 * every colour of the product in list order with the same sizes for that colour alone.
 *
 * @mixin FinishedStockLevel
 */
final class FinishedStockResource extends JsonResource
{
    /**
     * @return array{
     *     units: int,
     *     sizes: list<array{size: array{id: int, label: string}, units: int}>,
     *     colours: list<array{colour: array{id: int, code: string, name: string, swatch: string}, units: int, sizes: list<array{size: array{id: int, label: string}, units: int}>}>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'units' => $this->units,
            'sizes' => self::sizes($this->sizes),
            'colours' => array_map(fn (array $row): array => [
                'colour' => self::colour($row['colour']),
                'units' => $row['units'],
                'sizes' => self::sizes($row['sizes']),
            ], $this->colours),
        ];
    }

    /**
     * @param  list<array{size: Size, units: int}>  $rows
     * @return list<array{size: array{id: int, label: string}, units: int}>
     */
    private static function sizes(array $rows): array
    {
        return array_map(fn (array $row): array => [
            'size' => ['id' => $row['size']->id, 'label' => $row['size']->label],
            'units' => $row['units'],
        ], $rows);
    }

    /** @return array{id: int, code: string, name: string, swatch: string} */
    private static function colour(Colour $colour): array
    {
        return ['id' => $colour->id, 'code' => $colour->code, 'name' => $colour->name, 'swatch' => $colour->swatch];
    }
}
