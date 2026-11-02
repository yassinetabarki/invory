<?php

use App\Models\ProductRestock;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Restock from the product page (INV-3-f, AC-7)
|--------------------------------------------------------------------------
|
| The product page's "Last restock" line comes in the deferred group `stock`; a restock started from a product's page comes back to it.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans] = restockCatalogue();
});

function lastRestockOf(): mixed
{
    return loadedPageProp(test()->actingAs(test()->user)->get(route('products.show', test()->jacket)), 'last_restock');
}

it('shows the newest restock not undone and the units it gave this product (AC-7)', function () {
    expect(lastRestockOf())->toBeNull();

    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00'));
    actingAs($this->user)->post(route('restocks.store'), restockBody([restockLine($this->jacket, ['Black' => ['S' => 3]])]))->assertSessionHasNoErrors();

    $this->travelTo(CarbonImmutable::parse('2026-10-25 14:32'));
    actingAs($this->user)->post(route('restocks.store'), restockBody([
        restockLine($this->jacket, ['Indigo' => ['S' => 4, 'M' => 8, 'L' => 6, 'XL' => 2], 'Black' => ['M' => 4]]),
        restockLine($this->jeans, ['Indigo' => ['M' => 6, 'L' => 4]]),
    ]))->assertSessionHasNoErrors();

    expect(lastRestockOf())->toBe(['restocked_on' => '2026-10-25', 'units' => 24]);

    // An undone restock doesn't count as the last one.
    actingAs($this->user)->post(route('restocks.undo', ProductRestock::query()->latest('id')->firstOrFail()))->assertSessionHasNoErrors();
    expect(lastRestockOf())->toBe(['restocked_on' => '2026-10-20', 'units' => 3]);

    // A restock dated earlier but recorded later is not the last one: the date decides.
    actingAs($this->user)->post(route('restocks.store'), restockBody([restockLine($this->jacket, ['Black' => ['L' => 1]])], ['restocked_on' => '2026-10-19']))->assertSessionHasNoErrors();
    expect(lastRestockOf())->toBe(['restocked_on' => '2026-10-20', 'units' => 3]);
});

it('comes back to the product page from a restock started there (AC-7)', function (Closure $lines, string $toast) {
    actingAs($this->user)
        ->post(route('restocks.store'), restockBody($lines($this->jacket, $this->jeans), ['from_product' => $this->jacket->id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $toast]);
})->with([
    'that product alone' => [
        fn ($jacket) => [restockLine($jacket, ['Indigo' => ['S' => 4, 'M' => 8, 'L' => 6, 'XL' => 2], 'Black' => ['M' => 4]])],
        '24 units of Denim Jacket restocked.',
    ],
    'one unit' => [fn ($jacket) => [restockLine($jacket, ['Black' => ['M' => 1]])], '1 unit of Denim Jacket restocked.'],
    'with another product added' => [
        fn ($jacket, $jeans) => [restockLine($jacket, ['Black' => ['M' => 24]]), restockLine($jeans, ['Indigo' => ['M' => 10]])],
        '34 units restocked.',
    ],
]);

it('goes to the Restocks list when the product it was started from is gone', function () {
    $tote = demoProduct('PRD-TOT-004');
    $tote->delete();

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([restockLine($this->jacket, ['Black' => ['M' => 1]])], ['from_product' => $tote->id]))
        ->assertRedirect(route('restocks.index'));
});
