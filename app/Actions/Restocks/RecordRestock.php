<?php

declare(strict_types=1);

namespace App\Actions\Restocks;

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductSizeStock;
use App\Models\Size;
use App\Models\User;
use App\Services\Products\ProductSizes;
use App\Services\Products\ProductSizeSet;
use App\Services\Restocks\RestockMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a restock of finished products (INV-3, ADR-0022): adds its units to the shelf (`product_size_stocks`) and records it, in one
 * transaction, all or nothing. It never touches materials, their history, sales, orders or the product rows.
 *
 * The products are locked first, in id order (the lock Update stock and Delete product take), then each is checked again under the
 * lock: deleted, colours or sizes that changed since the page, or a variant that would go over 1,000,000 units. Every refusal is
 * reported at once, on the keys the page shows them under, and nothing is written.
 */
final readonly class RecordRestock
{
    public const MAX_UNITS = 1000000;

    public function __construct(private ProductSizes $productSizes) {}

    /**
     * @param  array{restocked_on: string, reference: ?string, note: ?string, products: list<array{product_id: int, units: array<int, array<int, int>>}>}  $data  units by colour id then size id, 0 for an empty field
     *
     * @throws ValidationException on `products.{i}.product` (deleted, changed: the page shows it at the top) or `products.{i}.units.{colour}.{size}` (over the limit)
     */
    public function handle(array $data, User $user): ProductRestock
    {
        return DB::transaction(function () use ($data, $user): ProductRestock {
            $ids = array_map(fn (array $line): int => $line['product_id'], $data['products']);
            $products = Product::withTrashed()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $errors = [];
            $adds = [];

            foreach ($data['products'] as $i => $line) {
                $product = $products->get($line['product_id']);

                if ($product === null) {
                    $errors["products.{$i}.product"] = RestockMessages::unknownProduct();

                    continue;
                }

                if ($product->trashed()) {
                    $errors["products.{$i}.product"] = RestockMessages::deleted($product->name);

                    continue;
                }

                $colours = $product->colours()->get()->keyBy('id');
                $sizeSet = $this->productSizes->fresh($product);
                $sizes = $sizeSet->sizes->keyBy('id');

                if (! UpdateFinishedStock::matches($line['units'], $colours->keys()->all(), $sizes->keys()->all())) {
                    $errors["products.{$i}.product"] = RestockMessages::changed($product->name);

                    continue;
                }

                $onShelf = ProductSizeStock::query()->where('product_id', $product->id)->get()
                    ->keyBy(fn (ProductSizeStock $row): string => $row->colour_id.'-'.$row->size_id);

                // Colours in list order, sizes in size order, so the messages read in the order of the page.
                foreach ($colours as $colourId => $colour) {
                    foreach ($sizes as $sizeId => $size) {
                        $units = $line['units'][$colourId][$sizeId];

                        if ($units === 0) {
                            continue;
                        }

                        $row = $onShelf->get($colourId.'-'.$sizeId);

                        if (($row->units ?? 0) + $units > self::MAX_UNITS) {
                            $errors["products.{$i}.units.{$colourId}.{$sizeId}"] = RestockMessages::overLimit(self::variant($colour, $size, $sizeSet));

                            continue;
                        }

                        $adds[] = ['product' => $product, 'position' => $i + 1, 'colour_id' => (int) $colourId, 'size_id' => (int) $sizeId, 'units' => $units, 'row' => $row];
                    }
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $restock = new ProductRestock([
                'restocked_on' => $data['restocked_on'],
                'reference' => $data['reference'],
                'note' => $data['note'],
                'units' => array_sum(array_column($adds, 'units')),
            ]);
            $restock->forceFill(['workshop_id' => $user->workshop_id, 'user_id' => $user->id])->save();

            $lines = [];
            $inserts = [];

            foreach ($adds as $add) {
                $lines[] = [
                    'workshop_id' => $restock->workshop_id, 'product_restock_id' => $restock->id, 'product_id' => $add['product']->id,
                    'colour_id' => $add['colour_id'], 'size_id' => $add['size_id'], 'position' => $add['position'], 'units' => $add['units'],
                    'created_at' => now(), 'updated_at' => now(),
                ];

                if ($add['row'] instanceof ProductSizeStock) {
                    $add['row']->increment('units', $add['units']);
                } else {
                    $inserts[] = [
                        'workshop_id' => $add['product']->workshop_id, 'product_id' => $add['product']->id, 'colour_id' => $add['colour_id'],
                        'size_id' => $add['size_id'], 'units' => $add['units'], 'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }

            $restock->lines()->insert($lines);

            if ($inserts !== []) {
                ProductSizeStock::query()->insert($inserts);
            }

            return $restock;
        });
    }

    /** "Indigo M", "Black, size 38", "Camel". */
    private static function variant(Colour $colour, Size $size, ProductSizeSet $sizes): string
    {
        return RestockMessages::variant($colour->name, $size->label, $sizes->oneSize);
    }
}
