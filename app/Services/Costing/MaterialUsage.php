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
     * @param  string|null  $effectiveQuantity  needed per unit of the product, waste included, scale 4; null ("Varies") only when the need differs across the variants that use the material (a By colour and size line, or a material on one), one figure when every such variant needs the same
     * @param  int  $unitsCoveredMin  units this material's stock covers on its own: the lowest over the colours and sizes that use it
     * @param  int  $unitsCoveredMax  the highest; equal to the lowest for a product with no By colour and size line
     * @param  int  $maxProducible  units of the product that can be made now (whole composition; the guaranteed figure when it varies)
     * @param  Material  $bottleneck  the material that limits the product (the binding colour and size's when it varies)
     * @param  array{material: Material, max_producible: int}|null  $nextLimit  only when the material is the bottleneck, and not its only material; never for a product that varies
     * @param  bool  $productVaries  the product has a By colour and size line: no next limit or suggestion, whatever this material's own need
     */
    public function __construct(
        public Product $product,
        public ?string $effectiveQuantity,
        public int $unitsCoveredMin,
        public int $unitsCoveredMax,
        public int $maxProducible,
        public Material $bottleneck,
        public bool $isBottleneck,
        public ?array $nextLimit,
        public bool $productVaries,
    ) {}
}
