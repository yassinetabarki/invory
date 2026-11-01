<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Size;
use App\Services\Products\ProductBarcodeLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProductBarcodes` (INV-8, ADR-0020): the product's barcodes by colour and size, the counts and the sentence the Generate window shows.
 * It reads what `ProductBarcodes::of()` worked out and runs no query.
 *
 * @mixin ProductBarcodeLevel
 */
final class ProductBarcodesResource extends JsonResource
{
    /**
     * @return array{
     *     missing: int,
     *     missing_names: string|null,
     *     with_barcode: int,
     *     colours: list<array{colour: array<string, mixed>, missing: int, variants: list<array{size: array{id: int, label: string}, sku: string, barcode: string|null, shared_with: list<array{id: int, sku: string}>}>}>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'missing' => $this->missing,
            'missing_names' => $this->missingNames,
            'with_barcode' => $this->withBarcode,
            'colours' => array_map(fn (array $row): array => [
                'colour' => (new ColourResource($row['colour']))->resolve($request),
                'missing' => $row['missing'],
                'variants' => array_map(fn (array $variant): array => [
                    'size' => self::size($variant['size']),
                    'sku' => $variant['sku'],
                    'barcode' => $variant['barcode'],
                    'shared_with' => $variant['shared_with'],
                ], $row['variants']),
            ], $this->colours),
        ];
    }

    /** @return array{id: int, label: string} */
    private static function size(Size $size): array
    {
        return ['id' => $size->id, 'label' => $size->label];
    }
}
