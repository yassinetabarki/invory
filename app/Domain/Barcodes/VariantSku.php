<?php

declare(strict_types=1);

namespace App\Domain\Barcodes;

/**
 * The SKU of a variant (INV-8, ADR-0020): the product's SKU, the colour's code and the size's label joined by "-"
 * ("PRD-JKT-001-IND-S", a shoe "PRD-SNK-003-BLK-38"). A "One size" product has no size part ("PRD-TOT-001-CAM"). Built on every read, never stored.
 */
final class VariantSku
{
    /** @param  string|null  $sizeLabel  null for a "One size" product */
    public static function of(string $sku, string $colourCode, ?string $sizeLabel): string
    {
        return $sizeLabel === null ? "{$sku}-{$colourCode}" : "{$sku}-{$colourCode}-{$sizeLabel}";
    }
}
