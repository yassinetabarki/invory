<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingResult;
use App\Domain\Costing\Margin;

/**
 * Everything the domain says about one product at one moment: the cost breakdown and the capacity ({@see CostingResult}), the
 * margin (null without a selling price) and the BOM the figures came from (so a "next limit" can be derived without another query).
 */
final readonly class ProductCosting
{
    /**
     * @param  list<BomLine>  $bom  The composition as domain input, in display order.
     */
    public function __construct(
        public int $productId,
        public array $bom,
        public CostingResult $result,
        public ?Margin $margin,
    ) {}

    /**
     * Whether the product can't be made now: `max_producible = 0`. A product without a composition (`null`) is not counted.
     */
    public function cantProduce(): bool
    {
        return $this->result->capacity->maxProducible === 0;
    }
}
