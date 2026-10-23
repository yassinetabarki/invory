<?php

use App\Models\Product;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The product_size_stocks table (S-56-a, ADR-0017)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The composite foreign key and the CHECK only exist on PostgreSQL (the parity run);
| the unique key exists on both, but is proved here with the other two. The rollback is exercised by the older migration tests,
| which roll this migration back first.
*/

it('makes PostgreSQL refuse a stock row with 0 units, a size twice for one product, or a workshop that is not its product\'s', function (Closure $override, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite foreign key and the CHECK are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $product = CurrentWorkshop::run($mine, fn () => Product::factory()->create(['category' => 'apparel']));
    [$small, $medium] = [Size::query()->where('label', 'S')->value('id'), Size::query()->where('label', 'M')->value('id')];
    $row = ['workshop_id' => $mine->id, 'product_id' => $product->id, 'size_id' => $small, 'units' => 3, 'created_at' => now(), 'updated_at' => now()];

    // The product already holds S, so a second row for S is refused.
    DB::table('product_size_stocks')->insert($row);

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names the constraint that fired.
    expect(fn () => DB::transaction(fn () => DB::table('product_size_stocks')->insert([...$row, 'size_id' => $medium, ...$override($other, $small)])))
        ->toThrow(QueryException::class, $constraint);
})->with([
    '0 units' => [fn () => ['units' => 0], 'product_size_stocks_units_check'],
    'the same size twice for one product' => [fn (Workshop $other, int $small) => ['size_id' => $small], 'product_size_stocks_product_id_size_id_unique'],
    'a workshop that is not its product\'s' => [fn (Workshop $other) => ['workshop_id' => $other->id], 'product_size_stocks_workshop_product_foreign'],
]);
