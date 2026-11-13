<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** What "Start cutting N units" would do to the stock of a product's materials, and whether it is allowed. */
final readonly class CutPlan
{
    /**
     * @param  list<MaterialTake>  $takes  one per stock in BOM first-appearance order (a per-colour material: one per colour, in the order
     *                                     the variants' colours come); [] for an empty BOM
     * @param  bool  $mixed  more than one variant and the product varies (by colour and size, or through a per-colour material):
     *                       no "at most N" number
     * @param  list<CutGroup>  $variants  per colour and size of a takeVariants() plan, the per-unit amounts to remember (unitsReturned 0); [] from take()
     */
    public function __construct(
        public int $units,
        public array $takes,
        public ?int $maxCuttable,
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
        public bool $allowed,
        public bool $mixed = false,
        public array $variants = [],
    ) {}

    public function hasComposition(): bool
    {
        return $this->takes !== [];
    }
}
