<?php

declare(strict_types=1);

namespace App\Services\Restocks;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Products\FinishedStockLevel;
use App\Services\Products\SizeLists;
use Illuminate\Database\Eloquent\Collection;

/**
 * "Scan or type a barcode" on New restock (INV-3, AC-4; ADR-0020, ADR-0022): which products, colour and size a number stands for.
 *
 * A number stands for one colour and size and for the product names in `barcode_names`. Its matches are the non-deleted products of
 * any status with one of those names whose size list has its size and which have its colour, by SKU. When the products of that name
 * no longer have its colour, or no longer have its size, the lookup says so (the human's answer of 2026-11-02 for the size); a number
 * the workshop doesn't have, or no product of its names at all, is unknown.
 */
final readonly class RestockBarcodeLookup
{
    public function __construct(private RestockBook $restocks, private SizeLists $sizeLists) {}

    /**
     * @return array{result: 'found', colour_id: int, size_id: int, matches: list<array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}>}|array{result: 'invalid'|'unknown'|'colour_gone'|'size_gone', message: string}
     */
    public function find(string $typed): array
    {
        $number = preg_replace('/\s+/', '', $typed) ?? '';

        if (preg_match('/^\d{13}$/', $number) !== 1) {
            return ['result' => 'invalid', 'message' => RestockMessages::notABarcode()];
        }

        $barcode = Barcode::query()->with(['colour', 'size.sizeList', 'names'])->where('number', $number)->first();

        if ($barcode === null) {
            return self::unknown($number);
        }

        /** @var Collection<int, Product> $named */
        $named = Product::query()->with('colours')->whereIn('name_key', $barcode->names->pluck('name_key')->all())
            ->orderBy('sku')->orderBy('id')->get();

        $withSize = $named->filter(fn (Product $product): bool => $this->sizeLists->category($product->category_id)->sizeList->sizes->contains('id', $barcode->size_id));
        $matches = $withSize->filter(fn (Product $product): bool => $product->colours->contains('id', $barcode->colour_id));

        $variant = RestockMessages::variant($barcode->colour->name, $barcode->size->label, $barcode->size->sizeList->code === SizeList::ONE_SIZE);

        if ($matches->isNotEmpty()) {
            return [
                'result' => 'found',
                'colour_id' => $barcode->colour_id,
                'size_id' => $barcode->size_id,
                'matches' => array_values($matches->map(fn (Product $product): array => $this->restocks->line($product))->all()),
            ];
        }

        if ($withSize->isNotEmpty()) {
            $product = $withSize->firstOrFail();

            return ['result' => 'colour_gone', 'message' => RestockMessages::colourGone($number, $variant, $product->name, $barcode->colour->name)];
        }

        if ($named->isNotEmpty()) {
            $product = $named->firstOrFail();

            return ['result' => 'size_gone', 'message' => RestockMessages::sizeGone($number, $variant, $product->name, $barcode->size->label)];
        }

        return self::unknown($number);
    }

    /** @return array{result: 'unknown', message: string} */
    private static function unknown(string $number): array
    {
        return ['result' => 'unknown', 'message' => RestockMessages::unknownBarcode($number)];
    }
}
