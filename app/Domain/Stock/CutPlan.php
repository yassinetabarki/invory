<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** What "Start cutting N units" would do to the stock of a product's materials, and whether it is allowed. */
final readonly class CutPlan
{
    /** @param list<MaterialTake> $takes one per material in BOM first-appearance order; [] for an empty BOM */
    public function __construct(
        public int $units,
        public array $takes,
        public ?int $maxCuttable,
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
        public bool $allowed,
    ) {}

    public function hasComposition(): bool
    {
        return $this->takes !== [];
    }
}
