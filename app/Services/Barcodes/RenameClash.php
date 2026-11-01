<?php

declare(strict_types=1);

namespace App\Services\Barcodes;

use App\Domain\Barcodes\NameKey;
use App\Domain\Barcodes\VariantNames;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Products\SizeLists;
use Illuminate\Database\Query\JoinClause;

/**
 * The refusal of a rename that would make two different numbers meet (INV-8-d, AC-5, ADR-0020). A rename carries the barcodes of the old
 * name to the new name; that is impossible for a colour and size where the OLD name and the NEW name each already have a number and the
 * numbers differ, because one name, colour and size has one number. The same number under both names, or a number under one name only,
 * is never a clash.
 *
 * Only the colours and sizes the product has AFTER the save count: a colour taken off in the same save carries nothing, so a clash on
 * it is no reason to refuse. A change of capitals or spaces only is not a rename (same {@see NameKey}) and never clashes.
 *
 * Checked twice: by the update request (so the sentence shows with the other errors) and by `UpdateProduct` under the product's and then the
 * workshop's row lock (so a barcode made while the form was open is caught too). A fixed number of queries: the clashes and their colours.
 */
final class RenameClash
{
    public function __construct(private readonly SizeLists $sizeLists) {}

    /**
     * The sentence for the Name field, or null when nothing stops the rename: "Trucker Jacket already has its own barcodes for Indigo S
     * and L, so Denim Jacket can't take that name. Choose another name." (one variant: "…its own barcode for Indigo S, so …").
     * Beyond three colours the first three are named, then "and 2 more colours".
     *
     * @param  Product  $product  as saved: its name is the OLD name, its `name_key` the old key
     * @param  list<int>  $colourIds  the colours the product has once the save is done
     * @param  ProductCategory  $category  the category it has once the save is done (its size list decides the sizes)
     */
    public function refusal(Product $product, string $newName, array $colourIds, ProductCategory $category): ?string
    {
        $newKey = NameKey::of($newName);

        if ($newKey === $product->name_key || $colourIds === []) {
            return null;
        }

        $sizeList = $this->sizeLists->of($category);
        $sizeIds = $sizeList->sizes->map(fn (Size $size): int => $size->id)->all();

        // The colour and size where both names have a number and the numbers differ (ONE query: the old name's rows joined to the new
        // name's rows of the same workshop, colour and size).
        $clashes = BarcodeName::query()
            ->join('barcode_names as renamed', fn (JoinClause $join) => $join
                ->on('renamed.workshop_id', '=', 'barcode_names.workshop_id')
                ->on('renamed.colour_id', '=', 'barcode_names.colour_id')
                ->on('renamed.size_id', '=', 'barcode_names.size_id'))
            ->where('barcode_names.name_key', $product->name_key)
            ->where('renamed.name_key', $newKey)
            ->whereColumn('renamed.barcode_id', '<>', 'barcode_names.barcode_id')
            ->whereIn('barcode_names.colour_id', $colourIds)
            ->whereIn('barcode_names.size_id', $sizeIds)
            ->toBase()
            ->get(['barcode_names.colour_id', 'barcode_names.size_id']);

        if ($clashes->isEmpty()) {
            return null;
        }

        $bySize = [];

        foreach ($clashes as $clash) {
            $bySize[(int) $clash->colour_id][(int) $clash->size_id] = true;
        }

        $oneSize = $sizeList->code === SizeList::ONE_SIZE;
        $variants = [];

        // The colours in list order (ONE query), each with its clashing sizes in size order.
        foreach (Colour::query()->whereIn('id', array_keys($bySize))->ordered()->get(['id', 'name']) as $colour) {
            $variants[] = [
                'colour' => $colour->name,
                'sizes' => array_values($sizeList->sizes->filter(fn (Size $size): bool => isset($bySize[$colour->id][$size->id]))->map(fn (Size $size): string => $size->label)->all()),
            ];
        }

        $new = NameKey::tidy($newName);
        $barcodes = $clashes->count() === 1 ? 'barcode' : 'barcodes';
        $names = VariantNames::list($variants, $oneSize, 3);

        return "{$new} already has its own {$barcodes} for {$names}, so {$product->name} can't take that name. Choose another name.";
    }
}
