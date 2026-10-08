<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/** What a product earns per unit: its selling price minus its unit cost. */
final readonly class Margin
{
    /**
     * @param  string  $amount  Selling price minus unit cost, scale 4. Negative when the product sells below its cost.
     * @param  string|null  $percent  Amount as a percentage of the selling price, scale 2. Null when the selling price is zero.
     */
    public function __construct(
        public string $amount,
        public ?string $percent,
    ) {}
}
