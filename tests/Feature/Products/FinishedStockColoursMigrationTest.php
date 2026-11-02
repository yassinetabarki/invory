<?php

use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Products\FinishedStock;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The finished stock moves into the first colour (INV-2-a, AC-1)
|--------------------------------------------------------------------------
|
| The migration is rolled back to the shape S-56 left (a row per product and size), the rows of before INV-2 are written in that shape,
| and `up()` runs on them: nothing is lost, nothing is counted twice, every unit lands in the product's first colour. SQLite rebuilds
| the table for the NOT NULL column, so the keys it must keep are checked on both engines; the PostgreSQL-only composite key is in
| FinishedStockSchemaTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** A stock row in the shape before INV-2 (no colour). */
function oldStockRow(Product $product, string $size, int $units): array
{
    return [
        'workshop_id' => $product->workshop_id, 'product_id' => $product->id, 'units' => $units, 'created_at' => now(), 'updated_at' => now(),
        'size_id' => Size::query()->where('label', $size)->whereIn('size_list_id', DB::table('product_categories')->where('id', $product->category_id)->select('size_list_id'))->value('id'),
    ];
}

/**
 * Every stock row as `[product sku, colour code, size label, units]`, in id order.
 *
 * @return list<array{string, string, string, int}>
 */
function stockRows(): array
{
    return DB::table('product_size_stocks')
        ->join('products', 'products.id', '=', 'product_size_stocks.product_id')
        ->join('colours', 'colours.id', '=', 'product_size_stocks.colour_id')
        ->join('sizes', 'sizes.id', '=', 'product_size_stocks.size_id')
        ->orderBy('product_size_stocks.id')
        ->get(['products.sku', 'colours.code', 'sizes.label', 'product_size_stocks.units'])
        ->map(fn (object $row): array => [$row->sku, $row->code, $row->label, (int) $row->units])
        ->all();
}

it('moves every unit into the first colour of its product, keeps each workshop\'s own, and gives the units back per size on down()', function () {
    $jacket = demoProduct('PRD-JKT-001');
    $tote = demoProduct('PRD-TOT-004');
    $jeans = demoProduct('PRD-JNS-005');
    $other = Workshop::factory()->create();
    $foreign = CurrentWorkshop::run($other, function (): Product {
        $product = Product::factory()->create(['category' => 'apparel', 'sku' => 'FOREIGN-1']);
        // Its workshop's own colours: Black is on the product too, but White comes first in list order.
        $product->colours()->attach(Colour::query()->whereIn('code', ['BLK', 'WHT'])->pluck('id')->all(), ['workshop_id' => $product->workshop_id]);

        return $product;
    });
    // The jacket has White and Black; the jeans only Black, which is then its first; the tote no colour at all (INV-1 made it impossible).
    $jacket->colours()->attach(demoColour('BLK')->id, ['workshop_id' => $jacket->workshop_id]);
    $jeans->colours()->sync([demoColour('BLK')->id => ['workshop_id' => $jeans->workshop_id]]);
    $tote->colours()->detach();

    productSizeStockColoursMigration()->down();
    expect(Schema::hasColumn('product_size_stocks', 'colour_id'))->toBeFalse();

    DB::table('product_size_stocks')->insert([
        oldStockRow($jacket, 'S', 3), oldStockRow($jacket, 'L', 5), oldStockRow($jacket, 'XL', 2),
        oldStockRow($jeans, 'M', 7),
        oldStockRow($tote, 'One size', 4),
        oldStockRow($foreign, 'M', 9),
    ]);

    productSizeStockColoursMigration()->up();

    expect(stockRows())->toBe([
        ['PRD-JKT-001', 'WHT', 'S', 3], ['PRD-JKT-001', 'WHT', 'L', 5], ['PRD-JKT-001', 'WHT', 'XL', 2],
        ['PRD-JNS-005', 'BLK', 'M', 7],
        ['PRD-TOT-004', 'WHT', 'One size', 4],
        ['FOREIGN-1', 'WHT', 'M', 9],
    ]);

    // The product with no colour was linked to its workshop's first colour, with its workshop; the others' links are as they were.
    expect($tote->colours()->get()->map(fn (Colour $colour): array => [$colour->code, $colour->pivot->workshop_id])->all())->toBe([['WHT', $tote->workshop_id]])
        ->and(DB::table('product_colours')->where('product_id', $foreign->id)->count())->toBe(2)
        ->and(DB::table('colours')->whereIn('id', DB::table('product_size_stocks')->where('product_id', $foreign->id)->select('colour_id'))->value('workshop_id'))->toBe($other->id);

    // The Denim Jacket reads White XS 0 · S 3 · M 0 · L 5 · XL 2 · XXL 0 · XXXL 0, and Black all 0.
    actingAs($this->user);
    $stock = app(FinishedStock::class)->of($jacket);

    expect(array_map(fn (array $colour): array => [$colour['colour']->name, $colour['units'], array_column($colour['sizes'], 'units')], $stock->colours))
        ->toBe([['White', 10, [0, 3, 0, 5, 2, 0, 0]], ['Black', 0, [0, 0, 0, 0, 0, 0, 0]]])
        ->and($stock->units)->toBe(10);

    // The keys of the new shape, kept by the rebuild SQLite makes: the colour is required and restricted, a row is unique per product,
    // colour and size, and the indexes and foreign keys of the older shape are still there.
    $colourColumn = collect(Schema::getColumns('product_size_stocks'))->firstWhere('name', 'colour_id');
    // Each foreign key is found by its own columns: on PostgreSQL there are two keys to products (product_id alone, and the
    // composite workshop_id + product_id of S-56), so the table they point to cannot identify one.
    $foreignKeys = collect(Schema::getForeignKeys('product_size_stocks'));
    $onDelete = fn (array $columns): ?string => $foreignKeys->first(fn (array $key): bool => $key['columns'] === $columns)['on_delete'] ?? null;

    expect($colourColumn['nullable'])->toBeFalse()
        ->and(Schema::hasIndex('product_size_stocks', ['product_id', 'colour_id', 'size_id'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('product_size_stocks', ['product_id', 'size_id'], 'unique'))->toBeFalse()
        ->and(Schema::hasIndex('product_size_stocks', ['colour_id']))->toBeTrue()
        ->and(Schema::hasIndex('product_size_stocks', ['size_id']))->toBeTrue()
        ->and($onDelete(['colour_id']))->toBe('restrict')
        ->and($onDelete(['size_id']))->toBe('restrict')
        ->and($onDelete(['product_id']))->toBe('cascade')
        ->and($onDelete(['workshop_id']))->toBe('restrict');

    $perSize = fn (): array => DB::table('product_size_stocks')
        ->join('sizes', 'sizes.id', '=', 'product_size_stocks.size_id')
        ->where('product_id', $jacket->id)->orderBy('product_size_stocks.id')->pluck('product_size_stocks.units', 'sizes.label')->map(fn (mixed $units): int => (int) $units)->all();

    // Back: one row per product and size again, with the old unique key, and the units as they were: S 3 · L 5 · XL 2.
    productSizeStockColoursMigration()->down();

    expect(Schema::hasColumn('product_size_stocks', 'colour_id'))->toBeFalse()
        ->and(Schema::hasIndex('product_size_stocks', ['product_id', 'size_id'], 'unique'))->toBeTrue()
        ->and(DB::table('product_size_stocks')->count())->toBe(6)
        ->and($perSize())->toBe(['S' => 3, 'L' => 5, 'XL' => 2]);

    // Units in two colours of one size are added together into one row.
    productSizeStockColoursMigration()->up();
    DB::table('product_size_stocks')->insert([
        'workshop_id' => $jacket->workshop_id, 'product_id' => $jacket->id, 'colour_id' => demoColour('BLK')->id,
        'size_id' => oldStockRow($jacket, 'XL', 0)['size_id'], 'units' => 6, 'created_at' => now(), 'updated_at' => now(),
    ]);
    productSizeStockColoursMigration()->down();

    expect(DB::table('product_size_stocks')->count())->toBe(6)
        ->and($perSize())->toBe(['S' => 3, 'L' => 5, 'XL' => 8]);
});
