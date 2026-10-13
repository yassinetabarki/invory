<?php

use App\Models\Sale;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoSalesSeeder;

it('seeds the 60 demo sales once and never again, whatever happens to the sales table', function () {
    $this->travelTo('2026-10-08 12:00:00');
    $this->seed(DemoCatalogSeeder::class);

    $this->seed(DemoSalesSeeder::class);
    $this->seed(DemoSalesSeeder::class);

    $first = Sale::query()->orderBy('id')->first();

    expect(Sale::query()->count())->toBe(60)
        // Relative to today (150 days ago for the oldest), nobody recorded them.
        ->and($first->sold_on->toDateString())->toBe('2026-05-11')
        ->and($first->user_id)->toBeNull();

    // A user who deleted every demo sale but one is not given the demo sales back.
    Sale::query()->where('id', '>', $first->id)->delete();
    $this->seed(DemoSalesSeeder::class);

    expect(Sale::query()->count())->toBe(1);
});
