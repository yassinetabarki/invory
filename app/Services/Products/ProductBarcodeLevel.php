<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Colour;
use App\Models\Size;

/**
 * The barcodes of a product (INV-8, ADR-0020): every colour of the product in list order, each with every size of its list in order, each
 * variant with its SKU, its number (or none) and the other products that show it. Whole numbers and text only, no money.
 */
final readonly class ProductBarcodeLevel
{
    /**
     * @param  int  $missing  the variants without a barcode, over every colour
     * @param  string|null  $missingNames  those variants as a sentence ("Black M and L, and Light wash S, M, L and XL"); null when none is missing
     * @param  int  $withBarcode  the variants that have one
     * @param  list<array{colour: Colour, missing: int, variants: list<array{size: Size, sku: string, barcode: string|null, shared_with: list<array{id: int, sku: string}>}>}>  $colours
     */
    public function __construct(
        public int $missing,
        public ?string $missingNames,
        public int $withBarcode,
        public array $colours,
    ) {}
}
