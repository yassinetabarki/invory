<?php

declare(strict_types=1);

namespace App\Services\Costing;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingResult;
use App\Domain\Costing\Margin;
use App\Domain\Costing\VariantCostingResult;

/**
 * Everything the domain says about one product at one moment: the cost breakdown and the capacity ({@see CostingResult}), the
 * margin (null without a selling price) and the BOM the figures came from (so a "next limit" can be derived without another query).
 *
 * A product with a "By colour and size" line, or a material kept per colour (INV-21), also carries {@see VariantCostingResult} in
 * `$variants`. Then `result`, `margin` and
 * `bom` are those of its binding variant (the one that makes the fewest): its guaranteed capacity and that variant's bottleneck, which is
 * what every screen that shows one figure keeps showing until cost and margin become ranges (INV-16-j).
 */
final readonly class ProductCosting
{
    /**
     * @param  list<BomLine>  $bom  The composition as domain input, in display order (the binding variant's for a varying product).
     * @param  VariantCostingResult|null  $variants  Null unless the product has a by-colour-and-size line.
     */
    public function __construct(
        public int $productId,
        public array $bom,
        public CostingResult $result,
        public ?Margin $margin,
        public ?VariantCostingResult $variants = null,
    ) {}

    /** Whether the product's cost and capacity vary by colour and size (some line is by colour and size and has a cell). */
    public function varies(): bool
    {
        return $this->variants !== null && $this->variants->varies;
    }

    /**
     * Whether the product is costed per colour and size: some line is by colour and size, or some material is kept per colour (INV-21).
     * Then each colour has its own capacity (and cost, with cost per colour), and `result` is the binding variant's.
     */
    public function perVariant(): bool
    {
        return $this->variants !== null && ($this->variants->varies || $this->variants->perColour);
    }

    /**
     * The stock of the bottleneck material that limits the capacity: in the binding variant's colour for a material kept per colour
     * ("0.0000" when it lacks that colour), the material's stock otherwise. Null without a bottleneck.
     */
    public function bottleneckStock(): ?string
    {
        $materialId = $this->result->capacity->bottleneckMaterialId;

        foreach ($this->bom as $line) {
            if ($line->materialId === $materialId) {
                return $line->stockMissing ? '0.0000' : $line->stockOnHand;
            }
        }

        return null;
    }

    /**
     * The lowest and highest material cost over every colour and size (both ends equal when nothing varies). Null when no colour and
     * size has a unit cost (every colour lacks the cost of a material with cost per colour, INV-21).
     *
     * @return array{min: string, max: string}|null
     */
    public function materialCostRange(): ?array
    {
        if ($this->variants === null) {
            return ['min' => $this->result->cost->materialCost, 'max' => $this->result->cost->materialCost];
        }

        return $this->variants->materialCostMin === null || $this->variants->materialCostMax === null
            ? null
            : ['min' => $this->variants->materialCostMin, 'max' => $this->variants->materialCostMax];
    }

    /**
     * The lowest and highest unit cost over every colour and size (both ends equal when nothing varies). Null when no colour and size
     * has a unit cost (every colour lacks the cost of a material with cost per colour, INV-21).
     *
     * @return array{min: string, max: string}|null
     */
    public function unitCostRange(): ?array
    {
        if ($this->variants === null) {
            return ['min' => $this->result->cost->unitCost, 'max' => $this->result->cost->unitCost];
        }

        return $this->variants->unitCostMin === null || $this->variants->unitCostMax === null
            ? null
            : ['min' => $this->variants->unitCostMin, 'max' => $this->variants->unitCostMax];
    }

    /**
     * The margin as a range: the amount is the selling price minus the highest unit cost (min) to minus the lowest (max); the percent
     * is null when the selling price is 0. Null without a selling price.
     *
     * @return array{amount: array{min: string, max: string}, percent: array{min: string, max: string}|null}|null
     */
    public function marginRange(): ?array
    {
        if ($this->variants === null) {
            if ($this->margin === null) {
                return null;
            }

            return [
                'amount' => ['min' => $this->margin->amount, 'max' => $this->margin->amount],
                'percent' => $this->margin->percent === null ? null : ['min' => $this->margin->percent, 'max' => $this->margin->percent],
            ];
        }

        $variants = $this->variants;

        if ($variants->marginAmountMin === null || $variants->marginAmountMax === null) {
            return null;
        }

        return [
            'amount' => ['min' => $variants->marginAmountMin, 'max' => $variants->marginAmountMax],
            'percent' => $variants->marginPercentMin === null || $variants->marginPercentMax === null
                ? null
                : ['min' => $variants->marginPercentMin, 'max' => $variants->marginPercentMax],
        ];
    }

    /**
     * The most units any one colour and size can make now; the capacity of the whole composition when the product doesn't vary.
     * Null without a composition.
     */
    public function best(): ?int
    {
        return $this->variants === null ? $this->result->capacity->maxProducible : $this->variants->best;
    }

    /**
     * Whether the product can't be made now at all: its best capacity is 0, so not even one colour and size can be made. A product
     * that only has a variant at 0 can still be made in the others. A product without a composition (`null`) is not counted.
     */
    public function cantProduce(): bool
    {
        return $this->best() === 0;
    }
}
