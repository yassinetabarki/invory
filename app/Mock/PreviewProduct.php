<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingResult;
use App\Domain\Costing\Margin;
use App\Models\Material;

/**
 * A fixture product after the production domain code has run on it: its composition resolved against the real materials,
 * its cost breakdown and capacity (CostingCalculator) and its margin (MarginCalculator).
 *
 * @phpstan-import-type FixtureProduct from PreviewFixture
 *
 * @phpstan-type ProductLine array{id: int, position: int, material: Material, notes: string}
 */
final readonly class PreviewProduct
{
    /**
     * @param  FixtureProduct  $fixture  The raw inputs.
     * @param  list<ProductLine>  $lines  The lines whose material exists, in display order (1-based `position`). `costing->cost->lines` follows the same order.
     * @param  list<BomLine>  $bom  The same lines as domain input.
     */
    public function __construct(
        public array $fixture,
        public array $lines,
        public array $bom,
        public CostingResult $costing,
        public ?Margin $margin,
    ) {}

    public function id(): int
    {
        return $this->fixture['id'];
    }

    public function name(): string
    {
        return $this->fixture['name'];
    }

    /** Units that can be made now, or null without a composition. */
    public function maxProducible(): ?int
    {
        return $this->costing->capacity->maxProducible;
    }
}
