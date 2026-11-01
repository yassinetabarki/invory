<?php

use App\Domain\Barcodes\VariantSku;

// INV-8-b: the SKU of a variant is built on every read (costing skill "Barcodes").

it('joins the product SKU, the colour code and the size', function (string $sku, string $code, ?string $size, string $expected) {
    expect(VariantSku::of($sku, $code, $size))->toBe($expected);
})->with([
    'a letter size' => ['PRD-JKT-001', 'IND', 'S', 'PRD-JKT-001-IND-S'],
    'a shoe size' => ['PRD-SNK-003', 'BLK', '38', 'PRD-SNK-003-BLK-38'],
    'a "One size" product has no size part' => ['PRD-TOT-001', 'CAM', null, 'PRD-TOT-001-CAM'],
]);
