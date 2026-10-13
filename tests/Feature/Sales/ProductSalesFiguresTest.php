<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The sales figures of the products pages, from the database (S-14-c)
|--------------------------------------------------------------------------
*/

it('counts the last 30 days of a product on the list and the page, and sends its 5 newest sales', function () {
    $this->travelTo('2026-10-08 12:00:00');
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    $none = Product::factory()->create();

    // 29 days ago is the first day of the window (inclusive), 30 days ago is out. The window ends today; nothing is in the future.
    Sale::factory()->for($product)->daysAgo(29)->create(['quantity' => 4, 'unit_price' => '10.5000']);
    Sale::factory()->for($product)->daysAgo(30)->create(['quantity' => 100, 'unit_price' => '1.0000']);
    Sale::factory()->for($product)->count(5)->sequence(...array_map(fn (int $days): array => ['sold_on' => now()->subDays($days)->toDateString()], [0, 1, 2, 3, 4]))
        ->create(['quantity' => 1, 'unit_price' => '2.2500']);
    Sale::factory()->for($other)->create(['quantity' => 7]);

    $list = actingAs($user)->get(route('products.index'));
    $units = collect(pageProp($list, 'products.data'))->pluck('units_sold_last_30_days', 'id')->all();

    // 4 + 5 units in the window; a product without a sale reads 0, never null.
    expect($units)->toEqual([$product->id => 9, $other->id => 7, $none->id => 0]);

    $show = actingAs($user)->get(route('products.show', $product));

    expect(loadedPageProp($show, 'sales_last_30_days'))->toBe(['revenue' => '53.2500', 'units_sold' => 9])
        // The newest 5, by day then id: the five of the last days, not the oldest ones.
        ->and(loadedPageProp($show, 'recent_sales.*.sold_on'))->toBe(['2026-10-08', '2026-10-07', '2026-10-06', '2026-10-05', '2026-10-04'])
        ->and(loadedPageProp($show, 'recent_sales.0.total'))->toBe('2.2500')
        ->and(loadedPageProp(actingAs($user)->get(route('products.show', $none)), 'sales_last_30_days'))->toBe(['revenue' => '0.0000', 'units_sold' => 0])
        ->and(pageProp(actingAs($user)->get(route('products.index')), 'products.data.*.units_sold_last_30_days'))->each->toBeInt();
});
