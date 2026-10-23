<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLineCut;
use App\Models\StockMovement;
use Illuminate\Testing\TestResponse;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Cutting by size takes the materials of all the units together (S-61-a: AC-3 and AC-4 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's Denim Jacket counted by size, S 10 · M 20 · L 15 · XL 5, on the demo stock (Denim 12oz Indigo 250 m, Metal shank
| button 17mm 900 pc, Polyester thread 20,000 m, Woven brand label 400 pc), with the demo stock history so the ledger balances. 14 jackets
| take 27.72 m, 112 pc, 1,764 m and 14 pc, as if they were one size. The moves themselves are in MoveSizesTest.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    DemoHistory::seed();
});

/**
 * Posts a move of the order's first line by size label, coming from the order's own page.
 *
 * @param  array<string, int>  $byLabel
 */
function cutBySize(Order $order, string $move, array $byLabel): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->firstOrFail()]), ['move' => $move, 'sizes' => sizeIds($byLabel)]);
}

/** The stock saved now of the four jacket materials, by SKU. */
function jacketMaterials(): array
{
    return Material::query()->whereIn('sku', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->orderBy('sku')->pluck('quantity_on_hand', 'sku')->all();
}

/**
 * The history entries of a type by SKU, in entry order: `[quantity, units, sizes]`.
 *
 * @return array<string, array{string, int|null, array<int, array{size_id: int, label: string, units: int}>|null}>
 */
function sizedEntries(StockMovementType $type): array
{
    return StockMovement::query()->where('type', $type)->orderBy('id')->get()
        ->mapWithKeys(fn (StockMovement $entry): array => [Material::query()->findOrFail($entry->material_id)->sku => [$entry->quantity, $entry->units, $entry->sizes]])
        ->all();
}

/** The sizes of an entry as the history stores them: id, label, units, in size order. */
function movedSizes(array $byLabel): array
{
    $ids = sizeIds(array_map(fn (): int => 1, $byLabel));

    return array_values(array_map(
        fn (string $label, int $id, int $units): array => ['size_id' => $id, 'label' => $label, 'units' => $units],
        array_keys($byLabel),
        array_keys($ids),
        $byLabel,
    ));
}

it('takes the materials of 14 jackets as one cut and puts the sizes on every history entry (AC-3)', function () {
    $order = bookSizedOrder();

    cutBySize($order, 'start_cutting', ['S' => 4, 'M' => 10])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '14 units of Denim Jacket moved to In cutting. Materials taken out of stock.']);

    $sizes = movedSizes(['S' => 4, 'M' => 10]);

    expect(jacketMaterials())->toBe(['MAT-BTN-017' => '788.0000', 'MAT-DEN-012' => '222.2800', 'MAT-LBL-WOV' => '386.0000', 'MAT-THR-PES' => '18236.0000'])
        ->and(OrderLineCut::query()->sole()->units)->toBe(14)
        ->and(sizedEntries(StockMovementType::Cutting))->toBe([
            'MAT-DEN-012' => ['27.7200', 14, $sizes],
            'MAT-BTN-017' => ['112.0000', 14, $sizes],
            'MAT-THR-PES' => ['1764.0000', 14, $sizes],
            'MAT-LBL-WOV' => ['14.0000', 14, $sizes],
        ]);

    $this->artisan('invory:stock:verify')->assertExitCode(0);
});

it('counts every size together against the stock: the button at 100 pc refuses S 10 and M 10 (AC-3)', function () {
    demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '100.0000']);
    $order = bookSizedOrder();
    $before = [jacketMaterials(), StockMovement::query()->orderBy('id')->get()->toArray(), OrderLineCut::query()->count(), sizedFigures($order)];

    cutBySize($order, 'start_cutting', ['S' => 10, 'M' => 10])
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors(['sizes' => 'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.']);

    expect([jacketMaterials(), StockMovement::query()->orderBy('id')->get()->toArray(), OrderLineCut::query()->count(), sizedFigures($order)])->toBe($before);
});

it('moves M 5 back from the latest cut and puts the size on the Back from cutting entries (AC-4)', function () {
    $order = bookSizedOrder();

    cutBySize($order, 'start_cutting', ['S' => 6])->assertSessionHasNoErrors();
    cutBySize($order, 'start_cutting', ['S' => 4, 'M' => 10])->assertSessionHasNoErrors();

    cutBySize($order, 'back_to_start', ['M' => 5])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To start. Materials returned to stock.']);

    $line = $order->lines()->firstOrFail();
    $m = movedSizes(['M' => 5]);

    // 20 jackets cut (39.6 m, 160 pc, 2,520 m, 20 pc), 5 of the second cut back (9.9 m, 40 pc, 630 m, 5 pc).
    expect(jacketMaterials())->toBe(['MAT-BTN-017' => '780.0000', 'MAT-DEN-012' => '220.3000', 'MAT-LBL-WOV' => '385.0000', 'MAT-THR-PES' => '18110.0000'])
        ->and(OrderLineCut::query()->orderBy('id')->pluck('units_returned')->all())->toBe([0, 5])
        ->and(sizedFigures($order)[$line->product_id]['M'])->toBe([20, 5, 0, 0])
        ->and([$line->refresh()->to_start, $line->in_cutting])->toBe([35, 15])
        ->and(sizedEntries(StockMovementType::CuttingReturn))->toBe([
            'MAT-DEN-012' => ['9.9000', 5, $m],
            'MAT-BTN-017' => ['40.0000', 5, $m],
            'MAT-THR-PES' => ['630.0000', 5, $m],
            'MAT-LBL-WOV' => ['5.0000', 5, $m],
        ]);

    $this->artisan('invory:stock:verify')->assertExitCode(0);
});
