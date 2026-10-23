<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutPlan;
use App\Enums\OrderLineMove;
use LogicException;

/**
 * The sentences of the order pages (S-49), built in one place so the order's `delete_refusal` and the refusal of the delete itself
 * can't say different things. A count of 1 reads in the singular.
 */
final readonly class OrderMessages
{
    /**
     * Why an order can't be deleted: `$held` units (in cutting plus ready to ship) still hold material taken out of stock.
     */
    public static function deleteRefusal(string $client, int $held): string
    {
        return trans_choice(
            "{1} :client can't be deleted: :count unit is in cutting or ready to ship. Move it back to To start first."
            ."|[2,*] :client can't be deleted: :count units are in cutting or ready to ship. Move them back to To start first.",
            $held,
            ['client' => $client],
        );
    }

    /** The toast of a move: "30 units of Denim Jacket moved to In cutting." / "5 units of Denim Jacket moved back to To start." */
    public static function moved(OrderLineMove $move, int $units, string $product): string
    {
        $replace = ['product' => $product, 'stage' => $move->toStage()->label()];

        return $move->isBack()
            ? trans_choice(
                '{1} :count unit of :product moved back to :stage.|[2,*] :count units of :product moved back to :stage.',
                $units,
                $replace,
            )
            : trans_choice(
                '{1} :count unit of :product moved to :stage.|[2,*] :count units of :product moved to :stage.',
                $units,
                $replace,
            );
    }

    /** What Start cutting adds to the toast, only when at least one material's stock changed (S-53). */
    public static function materialsTaken(): string
    {
        return __('Materials taken out of stock.');
    }

    /** What Move back to To start adds to the toast, only when at least one material's stock changed (S-53). */
    public static function materialsReturned(): string
    {
        return __('Materials returned to stock.');
    }

    /**
     * Why a cut of `$plan->units` is refused, and the same text the preview shows (S-53, ADR-0014): the plan's maximum and its
     * bottleneck, which is named also when several materials are short.
     *
     * @throws LogicException If the plan is allowed (there is nothing to refuse).
     */
    public static function notEnoughStock(CutPlan $plan): string
    {
        if ($plan->allowed || $plan->maxCuttable === null || $plan->bottleneckMaterialName === null) {
            throw new LogicException('A cut that is allowed has no short-stock message.');
        }

        $replace = ['material' => $plan->bottleneckMaterialName, 'max' => $plan->maxCuttable];

        return $plan->maxCuttable >= 1
            ? __('Not enough stock to cut :units units. You can cut at most :max now: :material runs short. Restock it first, or cut :max or fewer.', [...$replace, 'units' => $plan->units])
            : trans_choice(
                '{1} Not enough stock to cut :count unit. You can\'t cut any now: restock :material first.|[2,*] Not enough stock to cut :count units. You can\'t cut any now: restock :material first.',
                $plan->units,
                $replace,
            );
    }

    /** Why a move is refused: the stage holds `$held` units now (the saved count, which may differ from the screen's). */
    public static function moveLimit(int $held): string
    {
        return trans_choice('{1} You can move at most :count unit.|[0,*] You can move at most :count units.', $held);
    }

    /** Why a size can't move that many units (S-61-a): its stage holds `$held` units of `$size` now. */
    public static function sizeMoveLimit(string $size, int $held): string
    {
        return trans_choice(
            '{1} You can move at most :count unit of :size.|[0,*] You can move at most :count units of :size.',
            $held,
            ['size' => self::sizeName($size)],
        );
    }

    /** A size's field of the move window holds something that is not a whole number of units, or a negative one (S-61-a). */
    public static function wholeUnits(): string
    {
        return __('Enter a whole number of units.');
    }

    /** Every size of a move is empty or 0 (S-61-a). */
    public static function atLeastOneToMove(): string
    {
        return __('Enter at least one unit to move.');
    }

    /** Why a deleted product's units can't start cutting. */
    public static function productDeleted(string $product): string
    {
        return __(':product was deleted, so it can\'t be cut.', ['product' => $product]);
    }

    /** Why a started line's quantity can't go lower: `$started` units are in cutting, ready to ship or shipped. */
    public static function quantityBelowStarted(int $started): string
    {
        return trans_choice(
            "{1} The quantity can't be less than the :count unit already in cutting, ready to ship or shipped."
            ."|[2,*] The quantity can't be less than the :count units already in cutting, ready to ship or shipped.",
            $started,
        );
    }

    /** Why a size's quantity can't go lower (S-61 AC-5): `$started` units of that size are in cutting, ready to ship or shipped. */
    public static function sizeBelowStarted(string $size, int $started): string
    {
        return trans_choice(
            "{1} :size can't be less than the :count unit already in cutting, ready to ship or shipped."
            ."|[2,*] :size can't be less than the :count units already in cutting, ready to ship or shipped.",
            $started,
            ['size' => self::sizeName($size, true)],
        );
    }

    /** A size's quantity is not a whole number from 0 to 1,000,000 (S-60 AC-2). */
    public static function sizeQuantity(): string
    {
        return __('Enter a whole number from 0 to 1,000,000.');
    }

    /** Every size of a line is empty or 0 (S-60 AC-2). */
    public static function atLeastOneUnit(string $product): string
    {
        return __('Enter at least one unit of :product.', ['product' => $product]);
    }

    /** The sizes of a line add up to more than a line can hold (S-60 AC-2). */
    public static function lineTooBig(): string
    {
        return __('A line can hold up to 1,000,000 units.');
    }

    /**
     * The product's sizes changed after the order form was built (F-64, S-60 F3): what was typed no longer fits, so nothing is saved.
     * Shown under the field that was submitted.
     */
    public static function orderSizesChanged(string $product): string
    {
        return __('The sizes of :product have changed. Enter its quantities again.', ['product' => $product]);
    }

    /** The sizes of the line changed after the move window was built (S-61 F3): nothing moves. Shown under `sizes`, the whole window. */
    public static function moveSizesChanged(string $product): string
    {
        return __('The sizes ordered for :product have changed. Enter the units again.', ['product' => $product]);
    }

    /** How a size is named in a sentence: a size of digits only reads "size 36", a letter size stays "M"; `$first` capitalises it. */
    private static function sizeName(string $label, bool $first = false): string
    {
        $name = ctype_digit($label) ? "size {$label}" : $label;

        return $first ? ucfirst($name) : $name;
    }

    /** Why a started line can't leave the order (also when its product is swapped, since lines are matched by product). */
    public static function startedLineRemoved(string $product, int $started): string
    {
        return trans_choice(
            "{1} :product can't be removed: :count unit has started.|[2,*] :product can't be removed: :count units have started.",
            $started,
            ['product' => $product],
        );
    }
}
