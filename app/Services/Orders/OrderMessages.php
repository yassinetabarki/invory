<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutPlan;
use App\Services\Products\SizeNames;
use Illuminate\Support\Number;
use LogicException;

/**
 * The sentences of the order pages (S-49, INV-13, INV-14), built in one place so the screens and the refusals can't say different
 * things. A count of 1 reads in the singular; counts are printed grouped ("1,000,000").
 */
final readonly class OrderMessages
{
    /**
     * Why a cut of `$plan->units` is refused, and the same text the preview shows (S-53, ADR-0014; Start batch since INV-5): the plan's
     * maximum and its bottleneck, which is named also when several materials are short.
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

    /** Why a cell's quantity can't go lower in Edit order (INV-14 AC-6): `$settled` units of it are delivered or cancelled. */
    public static function sizeBelowSettled(string $variant, int $settled): string
    {
        return trans_choice(
            "{1} :variant can't be less than the :count unit already delivered or cancelled."
            ."|[2,*] :variant can't be less than the :count units already delivered or cancelled.",
            $settled,
            ['variant' => SizeNames::inText($variant, true), 'count' => self::count($settled)],
        );
    }

    /** Why a line can't leave the order (INV-14 AC-6; also when its product is swapped, since lines are matched by product). */
    public static function settledLineRemoved(string $product, int $settled): string
    {
        return trans_choice(
            "{1} :product can't be removed: :count unit is delivered or cancelled.|[2,*] :product can't be removed: :count units are delivered or cancelled.",
            $settled,
            ['product' => $product, 'count' => self::count($settled)],
        );
    }

    /** A cell's quantity is not a whole number from 0 to 1,000,000 (S-60 AC-2). */
    public static function sizeQuantity(): string
    {
        return __('Enter a whole number from 0 to 1,000,000.');
    }

    /** Every colour and size of a line is empty or 0 (S-60 AC-2; INV-13 AC-3). */
    public static function atLeastOneUnit(string $product): string
    {
        return __('Enter at least one unit of :product, or remove it from this order.', ['product' => $product]);
    }

    /** An order with no product (INV-13 AC-3). */
    public static function noProduct(): string
    {
        return __('Add at least one product to this order.');
    }

    /**
     * A colour and size in a sentence (INV-13): "Indigo M", "Black, size 38", or the colour alone for a "One size" line ("Camel").
     */
    public static function variant(string $colour, string $sizeLabel, bool $oneSize): string
    {
        return match (true) {
            $oneSize => $colour,
            ctype_digit($sizeLabel) => "{$colour}, size {$sizeLabel}",
            default => "{$colour} {$sizeLabel}",
        };
    }

    /** The sizes of a line add up to more than a line can hold (S-60 AC-2). */
    public static function lineTooBig(): string
    {
        return __('A line can hold up to 1,000,000 units.');
    }

    /**
     * The product's colours or sizes changed after the order form was built (F-64, S-60 F3, INV-13): what was typed no longer fits, so
     * nothing is saved.
     */
    public static function orderSizesChanged(string $product): string
    {
        return __('The colours or sizes of :product have changed. Enter its quantities again.', ['product' => $product]);
    }

    // ---------------------------------------------------------------------------------------------------------------------------------
    // INV-14: Mark delivered, its move back and Cancel units.
    // ---------------------------------------------------------------------------------------------------------------------------------

    /** "5 units of Denim Jacket marked delivered." */
    public static function delivered(int $units, string $product): string
    {
        return trans_choice('{1} :count unit of :product marked delivered.|[2,*] :count units of :product marked delivered.', $units, ['product' => $product, 'count' => self::count($units)]);
    }

    /** "5 units of Denim Jacket moved back to To deliver." */
    public static function movedBack(int $units, string $product): string
    {
        return trans_choice('{1} :count unit of :product moved back to To deliver.|[2,*] :count units of :product moved back to To deliver.', $units, ['product' => $product, 'count' => self::count($units)]);
    }

    /** "5 units of Denim Jeans 5-pocket cancelled." */
    public static function cancelled(int $units, string $product): string
    {
        return trans_choice('{1} :count unit of :product cancelled.|[2,*] :count units of :product cancelled.', $units, ['product' => $product, 'count' => self::count($units)]);
    }

    /** Nothing typed in a window. `$what` is "mark delivered", "move back" or "cancel". */
    public static function atLeastOne(string $what): string
    {
        return __('Enter at least one unit to :what.', ['what' => $what]);
    }

    /** "Indigo S: Enter a whole number of units." */
    public static function wholeUnits(string $variant): string
    {
        return SizeNames::inText($variant, true).': '.__('Enter a whole number of units.');
    }

    /** "You can mark at most 2 units of Indigo S: only 2 are on the shelf." */
    public static function deliverShelfLimit(string $variant, int $max): string
    {
        return trans_choice(
            '{0} You can\'t mark any unit of :variant: none is on the shelf.|{1} You can mark at most :count unit of :variant: only :count is on the shelf.|[2,*] You can mark at most :count units of :variant: only :count are on the shelf.',
            $max,
            ['variant' => SizeNames::inText($variant), 'count' => self::count($max)],
        );
    }

    /** "You can mark at most 4 units of Indigo S: only 4 are left to deliver." */
    public static function deliverOrderLimit(string $variant, int $max): string
    {
        return trans_choice(
            '{0} You can\'t mark any unit of :variant: none is left to deliver.|{1} You can mark at most :count unit of :variant: only :count is left to deliver.|[2,*] You can mark at most :count units of :variant: only :count are left to deliver.',
            $max,
            ['variant' => SizeNames::inText($variant), 'count' => self::count($max)],
        );
    }

    /** "You can move back at most 5 units of Indigo XL." */
    public static function moveBackLimit(string $variant, int $max): string
    {
        return trans_choice(
            '{0} You can\'t move back any unit of :variant.|{1} You can move back at most :count unit of :variant.|[2,*] You can move back at most :count units of :variant.',
            $max,
            ['variant' => SizeNames::inText($variant), 'count' => self::count($max)],
        );
    }

    /** "You can cancel at most 10 units of Indigo L." */
    public static function cancelLimit(string $variant, int $max): string
    {
        return trans_choice(
            '{0} You can\'t cancel any unit of :variant.|{1} You can cancel at most :count unit of :variant.|[2,*] You can cancel at most :count units of :variant.',
            $max,
            ['variant' => SizeNames::inText($variant), 'count' => self::count($max)],
        );
    }

    /** Mark delivered, when the shelf or the order changed so a field no longer fits. */
    public static function shelfOrOrderChanged(): string
    {
        return __('The shelf or this order changed since you opened this page.');
    }

    /** Move back and Cancel units, when the order changed so a field no longer fits. */
    public static function orderChanged(): string
    {
        return __('This order changed since you opened this page.');
    }

    /** Move back past what a shelf figure can hold (INV-3's limit). */
    public static function shelfFull(string $variant): string
    {
        return __(':variant can\'t go over 1,000,000 units on the shelf.', ['variant' => SizeNames::inText($variant, true)]);
    }

    private static function count(int $units): string
    {
        return (string) Number::format($units, locale: 'en');
    }
}
