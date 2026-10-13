<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The sales table and its policy (S-14-a)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The PostgreSQL CHECKs are only exercised on PostgreSQL (the parity run).
*/

it('creates sales with the agreed indexes, foreign keys and a plain stored day', function () {
    expect(Schema::hasColumns('sales', ['id', 'product_id', 'quantity', 'unit_price', 'sold_on', 'note', 'user_id', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasIndex('sales', ['sold_on', 'id']))->toBeTrue()
        ->and(Schema::hasIndex('sales', ['product_id', 'sold_on']))->toBeTrue();

    $product = Product::factory()->create();
    $user = User::factory()->create();
    $sale = Sale::factory()->for($product)->create(['sold_on' => '2026-10-08', 'user_id' => $user->id]);

    // The day is stored as a plain "Y-m-d".
    expect(DB::table('sales')->where('id', $sale->id)->value('sold_on'))->toBe('2026-10-08')
        ->and($sale->fresh()->sold_on->toDateString())->toBe('2026-10-08');

    // restrictOnDelete: a product that has sales can't be hard-deleted (products are soft-deleted, and the sale stays).
    expect(fn () => DB::transaction(fn () => $product->forceDelete()))->toThrow(QueryException::class);

    // nullOnDelete: the sale outlives the user who recorded it.
    $user->delete();

    expect($sale->fresh()->user_id)->toBeNull();
});

it('refuses out-of-range rows with the PostgreSQL CHECK constraints', function (array $override) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The CHECK constraints are PostgreSQL-only.');
    }

    $row = ['product_id' => Product::factory()->create()->id, 'quantity' => 1, 'unit_price' => '1.0000', 'sold_on' => '2026-10-08', 'created_at' => now(), 'updated_at' => now(), ...$override];

    // A savepoint, so the rejected row doesn't abort the surrounding transaction.
    expect(fn () => DB::transaction(fn () => DB::table('sales')->insert($row)))->toThrow(QueryException::class);
})->with([
    'quantity 0' => [['quantity' => 0]],
    'quantity over a million' => [['quantity' => 1000001]],
    'negative unit price' => [['unit_price' => '-0.0001']],
]);

it('lets a signed-in user do everything with sales, and nobody else', function (string $ability, bool $asUser, bool $expected) {
    $sale = Sale::factory()->create();
    $gate = $asUser ? Gate::forUser(User::factory()->create()) : Gate::forUser(null);
    $argument = in_array($ability, ['viewAny', 'create'], true) ? Sale::class : $sale;

    expect($gate->allows($ability, $argument))->toBe($expected);
})->with(function () {
    foreach (['viewAny', 'create', 'update', 'delete'] as $ability) {
        yield "guest, {$ability}" => [$ability, false, false];
        yield "user, {$ability}" => [$ability, true, true];
    }
});
