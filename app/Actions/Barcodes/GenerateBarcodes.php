<?php

declare(strict_types=1);

namespace App\Actions\Barcodes;

use App\Domain\Barcodes\Ean13;
use App\Domain\Barcodes\VariantNames;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\SizeList;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Barcodes\GeneratedBarcodes;
use App\Services\Products\ProductBarcodes;
use App\Services\Products\SizeLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Generate" on the Barcodes tab (INV-8-c, AC-3, ADR-0020): gives each asked variant (a colour and a size) of a product the next
 * in-store number of the workshop, ALL OR NOTHING. A variant whose name, colour and size already has a number is "already used" and
 * keeps it: no second barcode is ever made for it.
 *
 * Lock order, the one every barcode writer uses: the product's row, then the workshop's. The counter (`workshops.last_barcode_serial`)
 * is read and saved under the workshop's lock, so two people generating at once can't be given the same number; the unique keys
 * refuse what could still slip through. Any exception rolls the whole transaction back, the counter included.
 */
final readonly class GenerateBarcodes
{
    public function __construct(private SizeLists $sizeLists, private ProductBarcodes $barcodes) {}

    /** The only refusal there is: the request and the check under the lock say the same thing, on `variants`. */
    public static function refusal(): string
    {
        return "Couldn't generate the barcodes. Try again.";
    }

    /**
     * @param  list<array{colour_id: int, size_id: int}>  $variants  the variants asked for, in any order
     *
     * @throws ValidationException on `variants` when a variant is not one of the product's colours and sizes now, or is asked twice; nothing is written
     */
    public function handle(Product $product, array $variants, User $actor): GeneratedBarcodes
    {
        $result = DB::transaction(function () use ($product, $variants, $actor): array {
            // The product's row lock first (ADR-0017): a colour change or a category change can't land between the check and the save.
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $colours = $locked->colours()->get()->keyBy('id');
            $sizes = $this->sizeLists->category($locked->category_id)->sizeList;
            $ordered = $this->inOrder($variants, array_values($colours->keys()->all()), $sizes);

            // The workshop's row lock, then the counter under it.
            $workshop = Workshop::query()->lockForUpdate()->findOrFail($locked->workshop_id);
            $serial = $workshop->last_barcode_serial;
            $existing = $this->existing($locked, $ordered);
            $now = now();
            $barcodes = $names = $usedIds = [];

            foreach ($ordered as $variant) {
                if (isset($existing[$variant['colour_id']][$variant['size_id']])) {
                    $usedIds[] = $existing[$variant['colour_id']][$variant['size_id']];

                    continue;
                }

                $number = Ean13::inStore(++$serial);
                $barcodes[$number] = [
                    'workshop_id' => $locked->workshop_id, 'number' => $number, 'colour_id' => $variant['colour_id'], 'size_id' => $variant['size_id'],
                    'created_by' => $actor->id, 'created_at' => $now, 'updated_at' => $now,
                ];
            }

            if ($barcodes !== []) {
                Barcode::query()->insert(array_values($barcodes));
                $ids = Barcode::query()->whereIn('number', array_keys($barcodes))->pluck('id', 'number');

                foreach ($barcodes as $number => $row) {
                    $names[] = [
                        'workshop_id' => $locked->workshop_id, 'barcode_id' => $ids[$number], 'name_key' => $locked->name_key,
                        'colour_id' => $row['colour_id'], 'size_id' => $row['size_id'], 'created_at' => $now, 'updated_at' => $now,
                    ];
                }

                BarcodeName::query()->insert($names);

                $workshop->last_barcode_serial = $serial;
                $workshop->save();
            }

            $first = $ordered[0];

            return [$locked, count($ordered), count($barcodes), $this->variantName($colours[$first['colour_id']], $sizes, $first['size_id']), $usedIds];
        });

        [$locked, $asked, $created, $variant, $usedIds] = $result;

        return new GeneratedBarcodes($locked->name, $asked, $created, $variant, $this->holders($locked, $usedIds));
    }

    /**
     * The asked variants checked against the locked product's colours and size list, in colour order (the workshop's list order, as
     * `Product::colours()` gives it) then size order, whatever order they were sent in.
     *
     * @param  list<array{colour_id: int, size_id: int}>  $variants
     * @param  list<int|string>  $colourIds  the product's colours in list order
     * @return non-empty-list<array{colour_id: int, size_id: int}>
     */
    private function inOrder(array $variants, array $colourIds, SizeList $sizeList): array
    {
        $colourRank = array_flip(array_map('intval', $colourIds));
        $sizeRank = array_flip($sizeList->sizes->map(fn (Size $size): int => $size->id)->all());
        $seen = [];

        foreach ($variants as $variant) {
            $key = $variant['colour_id'].'-'.$variant['size_id'];

            if (! isset($colourRank[$variant['colour_id']], $sizeRank[$variant['size_id']]) || isset($seen[$key])) {
                throw ValidationException::withMessages(['variants' => self::refusal()]);
            }

            $seen[$key] = true;
        }

        if ($variants === []) {
            throw ValidationException::withMessages(['variants' => self::refusal()]);
        }

        usort($variants, fn (array $a, array $b): int => [$colourRank[$a['colour_id']], $sizeRank[$a['size_id']]] <=> [$colourRank[$b['colour_id']], $sizeRank[$b['size_id']]]);

        return $variants;
    }

    /**
     * The barcodes that already stand for the product's name in the asked variants (ONE query): colour id → size id → barcode id.
     *
     * @param  non-empty-list<array{colour_id: int, size_id: int}>  $variants
     * @return array<int, array<int, int>>
     */
    private function existing(Product $locked, array $variants): array
    {
        $found = [];

        $rows = BarcodeName::query()
            ->where('name_key', $locked->name_key)
            ->whereIn('colour_id', array_unique(array_column($variants, 'colour_id')))
            ->whereIn('size_id', array_unique(array_column($variants, 'size_id')))
            ->get(['barcode_names.barcode_id', 'barcode_names.colour_id', 'barcode_names.size_id']);

        foreach ($rows as $row) {
            $found[$row->colour_id][$row->size_id] = $row->barcode_id;
        }

        return $found;
    }

    /** "Light wash S", "Light wash size 36", "Camel": the way the toast names one variant. */
    private function variantName(Colour $colour, SizeList $sizeList, int $sizeId): string
    {
        $size = $sizeList->sizes->firstWhere('id', $sizeId);

        return VariantNames::one($colour->name, $sizeList->code === SizeList::ONE_SIZE || $size === null ? null : $size->label);
    }

    /**
     * The SKUs of the other non-deleted products that show the numbers already used, each once, in SKU order: the rule behind "Shared
     * with" on the tab, so the toast and the tab never disagree.
     *
     * @param  list<int>  $barcodeIds
     * @return list<string>
     */
    private function holders(Product $product, array $barcodeIds): array
    {
        $skus = [];

        foreach ($this->barcodes->sharedWith($barcodeIds, $product->id) as $holders) {
            foreach ($holders as $holder) {
                $skus[$holder['id']] = $holder['sku'];
            }
        }

        asort($skus, SORT_STRING);

        return array_values($skus);
    }
}
