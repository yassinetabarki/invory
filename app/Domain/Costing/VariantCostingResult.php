<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/**
 * The costing of a product over its variants (ADR-0027 §5, appendix B). Money is scale 4, percents scale 2, capacities whole.
 */
final readonly class VariantCostingResult
{
    /**
     * @param  bool  $varies  Some line is by colour and size.
     * @param  list<VariantResult>  $variants  In the order given.
     * @param  list<ColourCapacity>  $colours  In the order each colour first appears in $variants.
     * @param  int|null  $guaranteed  Smallest capacity over the variants; null when every composition is empty.
     * @param  int|null  $best  Largest capacity over the variants; null when every composition is empty.
     * @param  string|null  $materialCostMin  The material cost, unit cost (and their maxima) are null when no variant has a unit cost (every colour lacks a cost of a
     *                                        material with cost per colour): nothing to range over, not "0.0000".
     * @param  string|null  $marginAmountMin  Null when there is no selling price, or no variant has a unit cost.
     * @param  string|null  $marginPercentMin  Null when there is no selling price or it is zero.
     * @param  array<int, MaterialSpan>  $materials  By material id, in composition order, over the variants that use the material.
     * @param  CostingResult  $single  Without a by-colour-and-size line: the one costing of the whole BOM. Otherwise the binding
     *                                 variant's, or the first variant's when every composition is empty.
     * @param  bool  $perColour  Some line is on a material kept per colour (INV-21): the product is costed per colour even when no line varies.
     * @param  int|null  $bindingBottleneckColourId  The colour of the binding bottleneck's stock; null for a one-stock material.
     * @param  bool  $bindingBottleneckMissing  The binding bottleneck material lacks that colour.
     * @param  int|null  $bestBottleneckColourId  The same for the best variant.
     */
    public function __construct(
        public bool $varies,
        public array $variants,
        public array $colours,
        public ?int $guaranteed,
        public ?int $best,
        public ?int $bindingColourId,
        public ?int $bindingSizeId,
        public ?int $bindingMaterialId,
        public ?string $bindingMaterialName,
        public ?int $bestColourId,
        public ?int $bestSizeId,
        public ?int $bestMaterialId,
        public ?string $bestMaterialName,
        public ?string $materialCostMin,
        public ?string $materialCostMax,
        public ?string $unitCostMin,
        public ?string $unitCostMax,
        public ?string $marginAmountMin,
        public ?string $marginAmountMax,
        public ?string $marginPercentMin,
        public ?string $marginPercentMax,
        public array $materials,
        public CostingResult $single,
        public bool $perColour = false,
        public ?int $bindingBottleneckColourId = null,
        public bool $bindingBottleneckMissing = false,
        public ?int $bestBottleneckColourId = null,
        public bool $bestBottleneckMissing = false,
    ) {}
}
