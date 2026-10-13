<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Models\Material;
use App\Models\Product;

/**
 * One product that uses a material, as {@see MaterialUsageService} reads it: what the material means for that product
 * ("What you can make with it" on the material page, `MaterialUsage` in resources/js/types/domain.ts).
 */
final readonly class MaterialUsage
{
    /**
     * @param  Product  $product  with its composition loaded
     * @param  string  $effectiveQuantity  needed per unit of the product, waste included, scale 4
     * @param  int  $unitsCovered  units this material's stock covers on its own
     * @param  int  $maxProducible  units of the product that can be made now (whole composition)
     * @param  Material  $bottleneck  the material that limits the product
     * @param  array{material: Material, max_producible: int}|null  $nextLimit  only when the material is the bottleneck, and not its only material
     */
    public function __construct(
        public Product $product,
        public string $effectiveQuantity,
        public int $unitsCovered,
        public int $maxProducible,
        public Material $bottleneck,
        public bool $isBottleneck,
        public ?array $nextLimit,
    ) {}
}
