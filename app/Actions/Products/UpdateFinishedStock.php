<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Update stock" (S-56-c, ADR-0017): sets the finished units of a product, size by size. It SETS counts (the last save wins, also over
 * units S-57 will add) and writes nothing else: no material, history entry, sale, order or product field, not even `updated_at`.
 * A size at 0 has no row.
 */
final readonly class UpdateFinishedStock
{
    /**
     * The product row is locked (the lock `UpdateProduct` takes), then its size list is read under it and the submitted sizes are
     * checked against it: the category may have changed since the request was checked.
     *
     * @param  array<int, int>  $unitsBySizeId  units (0 or more) of every size of the product's list, by size id
     *
     * @throws ValidationException on `sizes` when they are not the product's sizes; nothing is written
     */
    public function handle(Product $product, array $unitsBySizeId): void
    {
        DB::transaction(function () use ($product, $unitsBySizeId): void {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $sizeIds = ProductCategory::query()->with('sizeList.sizes')->findOrFail($locked->category_id)
                ->sizeList->sizes->map(fn (Size $size): int => $size->id)->all();

            if (! self::sameSizes(array_keys($unitsBySizeId), $sizeIds)) {
                throw ValidationException::withMessages(['sizes' => self::sizesChanged($locked->name)]);
            }

            $rows = $locked->sizeStocks()->get()->keyBy('size_id');

            foreach ($sizeIds as $sizeId) {
                $units = $unitsBySizeId[$sizeId];
                $row = $rows->get($sizeId);

                if ($units === 0) {
                    $row?->delete();
                } elseif ($row === null) {
                    $locked->sizeStocks()->make(['size_id' => $sizeId, 'units' => $units])
                        ->forceFill(['workshop_id' => $locked->workshop_id])
                        ->save();
                } elseif ($row->units !== $units) {
                    $row->update(['units' => $units]);
                }
            }
        });
    }

    /** The refusal when the sizes submitted are not the product's sizes (shared with the request). */
    public static function sizesChanged(string $product): string
    {
        return __('The sizes of :product have changed. Enter the stock again.', ['product' => $product]);
    }

    /**
     * Whether the submitted size ids are exactly the list's, in any order.
     *
     * @param  array<int|string, mixed>  $submitted
     * @param  array<int, int>  $expected
     */
    public static function sameSizes(array $submitted, array $expected): bool
    {
        $submitted = array_map('strval', $submitted);
        $expected = array_map('strval', $expected);
        sort($submitted);
        sort($expected);

        return $submitted === $expected;
    }
}
