<?php

use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The product_size_stocks table (S-56-a, ADR-0017; INV-2-a, ADR-0019)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migrations. The composite foreign keys and the CHECK only exist on PostgreSQL (the parity run);
| the unique key exists on both, but is proved here with the others. The rollback is exercised by the older migration tests, which
| roll the colour migration (INV-2-a) and this one back first, and by FinishedStockColoursMigrationTest.
*/

it('makes PostgreSQL refuse a stock row with 0 units, a variant twice, a colour the product does not have, or a workshop that is not its product\'s', function (Closure $override, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite foreign keys and the CHECK are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $product = CurrentWorkshop::run($mine, fn () => Product::factory()->create(['category' => 'apparel']));
    [$white, $ecru] = CurrentWorkshop::run($mine, fn () => Colour::query()->orderBy('position')->limit(2)->pluck('id')->all());
    [$small, $medium] = [Size::query()->where('label', 'S')->value('id'), Size::query()->where('label', 'M')->value('id')];
    $row = ['workshop_id' => $mine->id, 'product_id' => $product->id, 'colour_id' => $white, 'size_id' => $small, 'units' => 3, 'created_at' => now(), 'updated_at' => now()];

    // The product has White (and not Ecru) and already holds S in it, so a second row for S in White is refused.
    DB::table('product_colours')->insert(['workshop_id' => $mine->id, 'product_id' => $product->id, 'colour_id' => $white]);
    DB::table('product_size_stocks')->insert($row);

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names the constraint that fired.
    expect(fn () => DB::transaction(fn () => DB::table('product_size_stocks')->insert([...$row, 'size_id' => $medium, ...$override($other, $small, $ecru)])))
        ->toThrow(QueryException::class, $constraint);
})->with([
    '0 units' => [fn () => ['units' => 0], 'product_size_stocks_units_check'],
    'the same size and colour twice for one product' => [fn (Workshop $other, int $small) => ['size_id' => $small], 'product_size_stocks_product_id_colour_id_size_id_unique'],
    'a colour the product does not have' => [fn (Workshop $other, int $small, int $ecru) => ['colour_id' => $ecru], 'product_size_stocks_product_colour_foreign'],
    'a workshop that is not its product\'s' => [fn (Workshop $other) => ['workshop_id' => $other->id], 'product_size_stocks_workshop_product_foreign'],
]);
