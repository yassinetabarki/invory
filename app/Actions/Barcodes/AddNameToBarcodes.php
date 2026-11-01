<?php

declare(strict_types=1);

namespace App\Actions\Barcodes;

use App\Models\BarcodeName;
use App\Models\Product;
use App\Models\Size;
use App\Services\Products\SizeLists;
use Illuminate\Database\Query\JoinClause;

/**
 * A rename keeps the barcodes (INV-8-d, AC-4, ADR-0020): for every colour and size the product has once the save is done, a barcode that
 * stands for the OLD name and not yet for the new one gets the new name too, in one insert. The old name keeps it, so the other products
 * of the old name keep it and labels already printed still scan. A colour taken off before or in the same save is not carried: under the
 * new name it shows "No barcode yet". Capitals or spaces only is the same key: nothing is written.
 *
 * Call it inside the transaction that saves the product, after its colours and category are saved and after `RenameClash` has said the
 * rename is free of clashes (a colour and size with a different number under each name is therefore never met here).
 */
final readonly class AddNameToBarcodes
{
    public function __construct(private SizeLists $sizeLists) {}

    /**
     * @param  Product  $product  saved with its new name, colours and category
     * @param  string  $oldKey  the `name_key` it had before the save
     */
    public function handle(Product $product, string $oldKey): void
    {
        if ($oldKey === $product->name_key) {
            return;
        }

        $colourIds = $product->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all();
        $sizeIds = $this->sizeLists->category($product->category_id)->sizeList->sizes->map(fn (Size $size): int => $size->id)->all();

        // The old name's rows that the new name lacks, in the product's colours and sizes (ONE query; the new name's row is what the left join misses).
        $missing = BarcodeName::query()
            ->leftJoin('barcode_names as renamed', fn (JoinClause $join) => $join
                ->on('renamed.workshop_id', '=', 'barcode_names.workshop_id')
                ->on('renamed.colour_id', '=', 'barcode_names.colour_id')
                ->on('renamed.size_id', '=', 'barcode_names.size_id')
                ->where('renamed.name_key', '=', $product->name_key))
            ->where('barcode_names.name_key', $oldKey)
            ->whereNull('renamed.id')
            ->whereIn('barcode_names.colour_id', $colourIds)
            ->whereIn('barcode_names.size_id', $sizeIds)
            ->toBase()
            ->get(['barcode_names.barcode_id', 'barcode_names.colour_id', 'barcode_names.size_id']);

        if ($missing->isEmpty()) {
            return;
        }

        $now = now();

        BarcodeName::query()->insert($missing->map(fn (object $row): array => [
            'workshop_id' => $product->workshop_id, 'barcode_id' => $row->barcode_id, 'name_key' => $product->name_key,
            'colour_id' => $row->colour_id, 'size_id' => $row->size_id, 'created_at' => $now, 'updated_at' => $now,
        ])->all());
    }
}
