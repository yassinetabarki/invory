<?php

declare(strict_types=1);

namespace App\Services\Restocks;

use App\Services\Orders\OrderMessages;

/**
 * Every message and toast of the restock pages (INV-3), so the requests, the Actions and their tests say the same words. A variant is
 * named as the card says: "Indigo M", "Black, size 38", and a "One size" product by its colour alone ("Camel").
 */
final readonly class RestockMessages
{
    /** "Indigo M", "Black, size 38", "Camel" (a "One size" product). */
    public static function variant(string $colour, string $sizeLabel, bool $oneSize): string
    {
        return match (true) {
            $oneSize => $colour,
            ctype_digit($sizeLabel) => "{$colour}, size {$sizeLabel}",
            default => "{$colour} {$sizeLabel}",
        };
    }

    /** "Indigo M: Enter a whole number from 0 to 1,000,000." */
    public static function wholeNumber(string $variant): string
    {
        return $variant.': '.OrderMessages::sizeQuantity();
    }

    /** "Indigo M: This would put more than 1,000,000 units on the shelf." */
    public static function overLimit(string $variant): string
    {
        return $variant.': '.__('This would put more than 1,000,000 units on the shelf.');
    }

    public static function lineEmpty(string $product): string
    {
        return __('Enter at least one unit of :product, or remove it from this restock.', ['product' => $product]);
    }

    public static function nothingTyped(): string
    {
        return __('Enter at least one unit to restock.');
    }

    public static function tooManyProducts(): string
    {
        return __('A restock can hold up to 50 products.');
    }

    public static function dateRequired(): string
    {
        return __('Enter the date.');
    }

    public static function dateInFuture(): string
    {
        return __("The date can't be in the future.");
    }

    public static function referenceTooLong(): string
    {
        return __('Keep the reference to 64 characters or fewer.');
    }

    public static function noteTooLong(): string
    {
        return __('Keep the note to 255 characters or fewer.');
    }

    public static function changed(string $product): string
    {
        return __('The sizes or colours of :product have changed. Enter its units again.', ['product' => $product]);
    }

    public static function deleted(string $product): string
    {
        return __(':product was deleted. Remove it from this restock.', ['product' => $product]);
    }

    /** A product id that isn't a product of the workshop at all (never sent by the page). */
    public static function unknownProduct(): string
    {
        return __('This product no longer exists. Remove it from this restock.');
    }

    /** "34 units restocked.", "1 unit restocked." */
    public static function restocked(int $units): string
    {
        return $units === 1 ? __('1 unit restocked.') : __(':units units restocked.', ['units' => number_format($units)]);
    }

    /** "24 units of Denim Jacket restocked." (from the product page, that product alone). */
    public static function productRestocked(int $units, string $product): string
    {
        return $units === 1
            ? __('1 unit of :product restocked.', ['product' => $product])
            : __(':units units of :product restocked.', ['units' => number_format($units), 'product' => $product]);
    }

    public static function undone(): string
    {
        return __('Restock undone.');
    }

    public static function alreadyUndone(): string
    {
        return __('This restock was already undone.');
    }

    /**
     * "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right
     * count with Update stock instead." A "One size" product reads "Leather Tote in Camel has …".
     */
    public static function cantUndo(string $product, string $colour, string $sizeLabel, bool $oneSize, int $onShelf, int $added): string
    {
        $variant = $oneSize ? "{$product} in {$colour}" : "{$product} in {$colour}, size {$sizeLabel},";

        return __("This restock can't be undone: :variant has :shelf on the shelf, fewer than the :added it added. Set the right count with Update stock instead.", [
            'variant' => $variant,
            'shelf' => $onShelf === 1 ? __('1 unit') : __(':units units', ['units' => number_format($onShelf)]),
            'added' => number_format($added),
        ]);
    }

    public static function notABarcode(): string
    {
        return __('A barcode has 13 digits. Check the number.');
    }

    /** "No product has the barcode 2000000000992. Check the label, or enter the units below." */
    public static function unknownBarcode(string $number): string
    {
        return __('No product has the barcode :number. Check the label, or enter the units below.', ['number' => $number]);
    }

    /** "2000000000077 is Light wash L of Denim Jacket, which no longer comes in Light wash." */
    public static function colourGone(string $number, string $variant, string $product, string $colour): string
    {
        return __(':number is :variant of :product, which no longer comes in :colour.', ['number' => $number, 'variant' => $variant, 'product' => $product, 'colour' => $colour]);
    }

    /** "2000000000088 is Indigo XXL of Denim Jacket, which no longer comes in size XXL." (the human's answer, 2026-11-02). */
    public static function sizeGone(string $number, string $variant, string $product, string $sizeLabel): string
    {
        return __(':number is :variant of :product, which no longer comes in size :size.', ['number' => $number, 'variant' => $variant, 'product' => $product, 'size' => $sizeLabel]);
    }
}
