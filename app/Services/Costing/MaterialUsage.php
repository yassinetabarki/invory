<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Models\Colour;
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
     * @param  array{material: Material, max_producible: int}|null  $nextLimit  only when the material is the bottleneck, and not its only material; for a product that varies, that of its binding colour and size
     * @param  bool  $productVaries  the product has a By colour and size line (a product only costed per colour has it false)
     * @param  list<array{colour: Colour, missing: bool, stock_on_hand: string, units_possible: array{min: int, max: int}}>|null  $byColour  a material kept per colour (INV-21): one reading per product colour that uses it, list order; null for a one-stock material. The covered range above leaves out the colours the material lacks
     * @param  Colour|null  $bottleneckColour  the colour of the bottleneck material's stock that limits the product; null for a one-stock bottleneck
     * @param  Colour|null  $limitColour  that colour when this material is the bottleneck, else null
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
        public ?array $byColour = null,
        public ?Colour $bottleneckColour = null,
        public ?Colour $limitColour = null,
    ) {}
}
