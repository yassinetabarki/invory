<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Inventory\StockLedgerVerifier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| A fresh install: `php artisan db:seed` on an empty database (S-08-d, AC-6 and AC-7)
|--------------------------------------------------------------------------
|
| Runs the whole documented demo-data step (DatabaseSeeder, not its parts), signs in as the demo user through the login route and
| reads, through the props of the screens, every figure the setup guide (README Step 6) promises. Then it runs the step again.
*/

it('a fresh install shows the demo workshop', function () {
    $this->travelTo(DEMO_NOW);
    $this->seed(DatabaseSeeder::class);

    $this->post(route('login.store'), ['email' => 'test@example.com', 'password' => 'password']);
    $this->assertAuthenticated();

    // Materials: 12, worth 10,544.300, and 4 that need a restock (3 low, 1 out of stock).
    $materials = $this->get(route('materials.index'))->assertOk();
    $toRestock = $this->get(route('materials.index', ['low_stock' => 1]))->assertOk();

    expect(pageProp($materials, 'materials.meta.total'))->toBe(12)
        ->and(pageProp($materials, 'summary'))->toBe(['materials_count' => 12, 'inventory_value' => '10544.3000'])
        ->and(pageProp($toRestock, 'materials.meta.total'))->toBe(4)
        ->and(collect(pageProp($toRestock, 'materials.data.*.stock_status'))->countBy()->all())->toBe(['low' => 3, 'out' => 1]);

    // Products: 7, the Denim Jacket at TND 35.754 per unit, 112 producible, limited by the button, 59.83% margin at TND 89.000.
    $products = $this->get(route('products.index'))->assertOk();
    $jacket = loadedPageProps($this->get(route('products.show', demoProduct('PRD-JKT-001')))->assertOk())['product'];

    expect(pageProp($products, 'products.meta.total'))->toBe(7)
        ->and($jacket['name'])->toBe('Denim Jacket')
        ->and($jacket['cost']['unit_cost'])->toBe('35.7540')
        ->and($jacket['capacity']['max_producible'])->toBe(112)
        ->and($jacket['capacity']['bottleneck']['name'])->toBe('Metal shank button 17mm')
        ->and($jacket['selling_price'])->toBe('89.0000')
        ->and($jacket['margin']['percent'])->toBe('59.83');

    // Sales: 60 in all (the log defaults to the last 30 days, so ask for the year).
    $sales = $this->get(route('sales.index', ['period' => 'this_year']))->assertOk();

    expect(pageProp($sales, 'sales.meta.total'))->toBe(60)
        ->and(Sale::query()->count())->toBe(60);

    // Dashboard, last 30 days: TND 16,850.000 from 48 sales, 296 units, and the top 5 by units.
    $dashboard = loadedPageProps($this->get(route('dashboard', ['period' => 'last_30_days']))->assertOk());

    expect($dashboard['stats'])->toMatchArray([
        'revenue' => '16850.0000',
        'sales_count' => 48,
        'units_sold' => 296,
        'materials_count' => 12,
        'inventory_value' => '10544.3000',
        'needs_restock_count' => 4,
        'low_stock_count' => 3,
        'out_of_stock_count' => 1,
        'products_count' => 7,
    ])->and(data_get($dashboard, 'top_products.by_units.*.product.name'))->toBe([
        'Classic White T-shirt', 'Denim Jeans 5-pocket', 'Denim Jacket', 'Leather Belt', 'Leather Tote',
    ])->and(data_get($dashboard, 'top_products.by_units.*.units_sold'))->toBe([142, 64, 38, 31, 12]);

    // History: every material has its "Created" entry, the button, the label and the denim also a restock and a price change.
    $perMaterial = StockMovement::query()->get()->groupBy('material_id')
        ->map(fn ($movements): array => $movements->countBy(fn (StockMovement $movement): string => $movement->type->value)->all());
    $restocked = $perMaterial->filter(fn (array $types): bool => ($types['restock'] ?? 0) === 1 && ($types['price_change'] ?? 0) === 1);

    expect($perMaterial)->toHaveCount(12)
        ->and($perMaterial->every(fn (array $types): bool => $types['created'] === 1))->toBeTrue()
        ->and($restocked->keys()->sort()->values()->all())->toBe(
            Material::query()->whereIn('sku', ['MAT-BTN-017', 'MAT-LBL-WOV', 'MAT-DEN-012'])->pluck('id')->sort()->values()->all(),
        )
        ->and(StockMovement::query()->count())->toBe(18)
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);

    // The demo data belongs to the workshop of the demo user, and only that workshop exists (ADR-0013, AC-7).
    $workshopId = User::query()->where('email', 'test@example.com')->value('workshop_id');

    expect(Workshop::query()->count())->toBe(1)
        ->and(ProductComponent::query()->count())->toBeGreaterThan(0);

    foreach ([Material::class, Product::class, ProductComponent::class, StockMovement::class, Sale::class] as $model) {
        expect($model::query()->where('workshop_id', $workshopId)->count())->toBe($model::query()->count(), $model);
    }
});

it('running the demo step again creates nothing twice and keeps a deleted demo product deleted', function () {
    $this->travelTo(DEMO_NOW);
    $this->seed(DatabaseSeeder::class);
    $belt = demoProduct('PRD-BLT-007');
    $belt->delete();
    $lines = ProductComponent::query()->count();

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Hash::check('password', User::query()->where('email', 'test@example.com')->firstOrFail()->password))->toBeTrue()
        ->and(Material::withTrashed()->count())->toBe(12)
        ->and(Product::withTrashed()->count())->toBe(7)
        ->and(Product::query()->count())->toBe(6)
        ->and(Product::onlyTrashed()->where('sku', 'PRD-BLT-007')->value('id'))->toBe($belt->id)
        ->and(ProductComponent::query()->count())->toBe($lines)
        ->and(Sale::query()->count())->toBe(60)
        ->and(StockMovement::query()->count())->toBe(18);
});
