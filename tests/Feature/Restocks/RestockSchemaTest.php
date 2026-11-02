<?php

use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductRestockLine;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The product_restocks and product_restock_lines tables (INV-3-a, ADR-0022)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The unique key exists on both engines; the composite keys and the CHECKs only exist
| on PostgreSQL (the parity run), so those rows are skipped on SQLite.
*/

it('keeps a line in its restock\'s workshop and its product\'s, one line per variant, with 1 to 1,000,000 units', function (Closure $write, string $postgres, ?string $sqlite) {
    if ($sqlite === null && DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite keys and CHECKs are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $line = CurrentWorkshop::run($mine, fn (): ProductRestockLine => ProductRestockLine::factory()->create());
    $theirProduct = CurrentWorkshop::run($other, fn (): Product => Product::factory()->create(['category' => 'apparel']));
    $theirRestock = CurrentWorkshop::run($other, fn (): ProductRestock => ProductRestock::factory()->create());

    $insert = fn (array $override): bool => DB::table('product_restock_lines')->insert(array_merge([
        'workshop_id' => $line->workshop_id, 'product_restock_id' => $line->product_restock_id, 'product_id' => $line->product_id,
        'colour_id' => $line->colour_id, 'size_id' => $line->size_id, 'position' => 1, 'units' => 5,
        'created_at' => now(), 'updated_at' => now(),
    ], $override));

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names what fired.
    expect(fn () => DB::transaction(fn () => $write($insert, $theirRestock, $theirProduct)))
        ->toThrow(QueryException::class, DB::getDriverName() === 'pgsql' ? $postgres : $sqlite);
})->with([
    'the same variant twice on one restock' => [
        fn (Closure $insert) => $insert([]),
        'product_restock_lines_variant_unique',
        'product_restock_lines.product_restock_id, product_restock_lines.product_id, product_restock_lines.colour_id, product_restock_lines.size_id',
    ],
    'a line on another workshop\'s restock' => [
        fn (Closure $insert, ProductRestock $theirs) => $insert(['product_restock_id' => $theirs->id]),
        'product_restock_lines_workshop_restock_foreign',
        null,
    ],
    'a line for another workshop\'s product' => [
        fn (Closure $insert, ProductRestock $theirRestock, Product $theirs) => $insert(['product_id' => $theirs->id, 'position' => 2]),
        'product_restock_lines_workshop_product_foreign',
        null,
    ],
    'a line of 0 units' => [
        fn (Closure $insert) => $insert(['position' => 2, 'units' => 0, 'size_id' => DB::table('sizes')->where('label', 'XL')->value('id')]),
        'product_restock_lines_units_check',
        null,
    ],
    'a line of 1,000,001 units' => [
        fn (Closure $insert) => $insert(['position' => 2, 'units' => 1000001, 'size_id' => DB::table('sizes')->where('label', 'XL')->value('id')]),
        'product_restock_lines_units_check',
        null,
    ],
    'a restock of 0 units' => [
        fn (Closure $insert) => DB::table('product_restocks')->insert([
            'workshop_id' => DB::table('product_restock_lines')->value('workshop_id'), 'restocked_on' => now()->toDateString(), 'units' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]),
        'product_restocks_units_check',
        null,
    ],
]);

it('makes a line in its restock\'s workshop, with a colour of its product', function () {
    $line = ProductRestockLine::factory()->create();

    expect($line->workshop_id)->toBe($line->restock->workshop_id)
        ->and($line->product->workshop_id)->toBe($line->workshop_id)
        ->and($line->product->colours()->pluck('colours.id')->all())->toContain($line->colour_id)
        ->and($line->restock->isUndone())->toBeFalse()
        ->and(ProductRestock::factory()->undone()->create()->isUndone())->toBeTrue();
});

it('reads a line\'s product after the product is deleted', function () {
    $line = ProductRestockLine::factory()->create();
    $line->product->delete();

    expect($line->fresh()?->product?->trashed())->toBeTrue();
});
