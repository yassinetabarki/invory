<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSizeStock;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Update stock" (S-56-c, ADR-0017; per colour since INV-2, ADR-0019): sets the finished units of a product, colour by colour and size
 * by size. It SETS counts (the last save wins, also over units S-57 will add) and writes nothing else: no material, history entry,
 * sale, order or product field, not even `updated_at`. A colour and size at 0 have no row.
 */
final readonly class UpdateFinishedStock
{
    /**
     * The product row is locked (the lock `UpdateProduct` takes), then its colours and its size list are read under it and the
     * submitted ones are checked against them: either may have changed since the request was checked. One transaction writes every
     * colour.
     *
     * @param  array<int, array<int, int>>  $units  units (0 or more) of every size of the product's list in every colour of the product, by colour id then size id
     *
     * @throws ValidationException on `stock` when the colours or sizes are not the product's; nothing is written
     */
    public function handle(Product $product, array $units): void
    {
        DB::transaction(function () use ($product, $units): void {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $colourIds = $locked->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all();
            $sizeIds = ProductCategory::query()->with('sizeList.sizes')->findOrFail($locked->category_id)
                ->sizeList->sizes->map(fn (Size $size): int => $size->id)->all();

            if (! self::matches($units, $colourIds, $sizeIds)) {
                throw ValidationException::withMessages(['stock' => self::stockChanged($locked->name)]);
            }

            $rows = ProductSizeStock::query()->where('product_id', $locked->id)->get()
                ->keyBy(fn (ProductSizeStock $row): string => $row->colour_id.'-'.$row->size_id);
            $delete = $insert = [];

            foreach ($colourIds as $colourId) {
                foreach ($sizeIds as $sizeId) {
                    $count = $units[$colourId][$sizeId];
                    $row = $rows->get($colourId.'-'.$sizeId);

                    if ($count === 0) {
                        if ($row !== null) {
                            $delete[] = $row->id;
                        }
                    } elseif ($row === null) {
                        $insert[] = [
                            'workshop_id' => $locked->workshop_id, 'product_id' => $locked->id, 'colour_id' => $colourId, 'size_id' => $sizeId,
                            'units' => $count, 'created_at' => now(), 'updated_at' => now(),
                        ];
                    } elseif ($row->units !== $count) {
                        $row->update(['units' => $count]);
                    }
                }
            }

            if ($delete !== []) {
                ProductSizeStock::query()->whereKey($delete)->delete();
            }

            if ($insert !== []) {
                ProductSizeStock::query()->insert($insert);
            }
        });
    }

    /** The refusal when the colours or sizes submitted are not the product's (shared with the request). */
    public static function stockChanged(string $product): string
    {
        return __('The sizes or colours of :product have changed. Enter the stock again.', ['product' => $product]);
    }

    /**
     * Whether the submitted counts are for exactly the product's colours, each with exactly the product's sizes.
     *
     * @param  array<int|string, mixed>  $submitted  colour id → size id → count
     * @param  array<int, int>  $colourIds
     * @param  array<int, int>  $sizeIds
     */
    public static function matches(array $submitted, array $colourIds, array $sizeIds): bool
    {
        if (! self::sameIds(array_keys($submitted), $colourIds)) {
            return false;
        }

        foreach ($submitted as $bySize) {
            if (! is_array($bySize) || ! self::sameIds(array_keys($bySize), $sizeIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the submitted ids are exactly the expected ones, in any order.
     *
     * @param  array<int|string, mixed>  $submitted
     * @param  array<int, int>  $expected
     */
    public static function sameIds(array $submitted, array $expected): bool
    {
        $submitted = array_map('strval', $submitted);
        $expected = array_map('strval', $expected);
        sort($submitted);
        sort($expected);

        return $submitted === $expected;
    }
}
