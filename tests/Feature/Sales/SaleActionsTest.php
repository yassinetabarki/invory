<?php

use App\Actions\Sales\DeleteSale;
use App\Actions\Sales\RecordSale;
use App\Actions\Sales\UpdateSale;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/*
|--------------------------------------------------------------------------
| The sale Actions (S-14-b)
|--------------------------------------------------------------------------
*/

it('records a sale for the actor', function () {
    $product = Product::factory()->create();
    $actor = User::factory()->create();

    $sale = app(RecordSale::class)->handle([
        'product_id' => $product->id, 'quantity' => 3, 'unit_price' => '89', 'sold_on' => '2026-10-08', 'note' => 'Web shop',
    ], $actor);

    expect($sale->exists)->toBeTrue()
        ->and($sale->fresh()->only(['product_id', 'quantity', 'unit_price', 'note', 'user_id']))
        ->toBe(['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '89.0000', 'note' => 'Web shop', 'user_id' => $actor->id])
        ->and($sale->fresh()->sold_on->toDateString())->toBe('2026-10-08');
});

it('updates a sale and keeps the user who recorded it', function () {
    $recorder = User::factory()->create();
    $sale = Sale::factory()->create(['user_id' => $recorder->id, 'quantity' => 1]);
    $other = Product::factory()->create();

    app(UpdateSale::class)->handle($sale, [
        'product_id' => $other->id, 'quantity' => 9, 'unit_price' => '12.3456', 'sold_on' => '2026-09-01', 'note' => null,
    ]);

    expect($sale->fresh()->only(['product_id', 'quantity', 'unit_price', 'note', 'user_id']))
        ->toBe(['product_id' => $other->id, 'quantity' => 9, 'unit_price' => '12.3456', 'note' => null, 'user_id' => $recorder->id])
        ->and($sale->fresh()->sold_on->toDateString())->toBe('2026-09-01');
});

it('deletes a sale for good', function () {
    $sale = Sale::factory()->create();

    app(DeleteSale::class)->handle($sale);

    expect(Sale::query()->count())->toBe(0);
});

it('rolls the sale back when something fails after the write', function () {
    Event::listen('eloquent.created: '.Sale::class, fn () => throw new RuntimeException('boom'));
    $product = Product::factory()->create();

    expect(fn () => app(RecordSale::class)->handle(
        ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '1', 'sold_on' => '2026-10-08'],
        User::factory()->create(),
    ))->toThrow(RuntimeException::class, 'boom')
        ->and(Sale::query()->count())->toBe(0);
});

it('never changes material stock or writes a stock movement (decision Q8)', function () {
    $product = Product::factory()->withComponents(2)->create();
    $actor = User::factory()->create();
    $snapshot = fn (): array => [
        Material::query()->orderBy('id')->get()->toArray(),
        StockMovement::query()->orderBy('id')->get()->toArray(),
    ];
    $before = $snapshot();

    $sale = app(RecordSale::class)->handle(
        ['product_id' => $product->id, 'quantity' => 1000000, 'unit_price' => '1', 'sold_on' => '2026-10-08'],
        $actor,
    );
    app(UpdateSale::class)->handle($sale, ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => '2', 'sold_on' => '2026-10-08', 'note' => null]);
    app(DeleteSale::class)->handle($sale);

    expect($before[0])->not->toBeEmpty()
        ->and($snapshot())->toBe($before);
});
