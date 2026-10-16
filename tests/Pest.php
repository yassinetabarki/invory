<?php

use App\Actions\Products\CreateProduct;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoSalesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // Feature tests assert Inertia props, not assets: a stale local Vite manifest (public/build) must not fail them.
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

// Browser tests (S-08, `composer test:browser`, not part of `composer test`): the plugin serves the app inside the test
// process, so the throwaway database of RefreshDatabase is the one the browser sees. No withoutVite(): the pages load the
// built assets of `npm run build`; the hot file points nowhere, so a running `composer dev` (public/hot) can't hijack them.
pest()->browser()->inChrome()->timeout(10000);

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Vite::useHotFile(storage_path('framework/testing/no-vite-hot')))
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/** Queries of the shared `reference` once-prop on a full load (units, material categories, product categories). A client that already holds it skips them. */
const REFERENCE_QUERIES = 3;

/*
 * Query budget: `expect(fn () => $this->get($url))->toRunAtMostQueries(10)` runs the closure and fails when it runs more SQL
 * queries than the budget (every query counts, including those of the session, the policies and the Resources).
 */
expect()->extend('toRunAtMostQueries', function (int $budget) {
    $queries = queryLog($this->value);

    expect(count($queries))->toBeLessThanOrEqual(
        $budget,
        'Query budget of '.$budget.' exceeded ('.count($queries).' queries): '.PHP_EOL.implode(PHP_EOL, $queries),
    );

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A valid Store/UpdateMaterialRequest payload (decimals as strings), with optional overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validMaterialPayload(array $overrides = []): array
{
    return [
        'sku' => 'FAB-DEN-001',
        'name' => 'Denim 12oz Indigo',
        'category' => 'fabric',
        'unit' => 'm',
        'unit_cost' => '8.5',
        'quantity_on_hand' => '250',
        'reorder_level' => '50',
        'supplier_name' => 'Candiani',
        'supplier_reference' => 'CD-1200',
        'color' => 'Indigo',
        'composition' => '98% cotton, 2% elastane',
        'notes' => 'Pre-washed',
        ...$overrides,
    ];
}

/**
 * The exact keys of the `Material` type in resources/js/types/domain.ts.
 *
 * @return list<string>
 */
function materialContractKeys(): array
{
    return [
        'id', 'sku', 'name', 'category', 'category_label', 'unit', 'unit_label', 'unit_cost',
        'quantity_on_hand', 'reorder_level', 'stock_value', 'stock_status', 'supplier_name',
        'supplier_reference', 'color', 'composition', 'notes', 'photo_url', 'used_in_count', 'created_at', 'updated_at',
    ];
}

/*
|--------------------------------------------------------------------------
| Demo-data helpers
|--------------------------------------------------------------------------
|
| The demo-data tests run with the clock frozen at 2026-10-08 12:00 and the 12 DemoCatalogSeeder materials in the
| database, so every "Expected" value of docs/plans/phase-2-ui-first-preview.md can be asserted exactly.
|
*/

/** The instant the demo sales (`DemoSalesSeeder`, dated relative to it) and the plan's "Expected" tables are relative to. */
const DEMO_NOW = '2026-10-08 12:00:00';

/**
 * Freeze the clock at DEMO_NOW, seed the 12 demo materials, the 7 demo products and the 60 demo sales (relative to the frozen
 * today) and return a verified user.
 * Call it from a `beforeEach` of a test file that needs the demo data: `beforeEach(fn () => $this->user = prepareDemo($this));`.
 */
function prepareDemo(TestCase $test): User
{
    $test->travelTo(CarbonImmutable::parse(DEMO_NOW));
    $test->seed(DemoCatalogSeeder::class);
    $test->seed(DemoSalesSeeder::class);

    return User::factory()->create();
}

/** The seeded (or factory-made) material with this SKU. */
function demoMaterial(string $sku): Material
{
    return Material::query()->where('sku', $sku)->firstOrFail();
}

/**
 * One Inertia prop of a page response by dot path, with `*` wildcards (`products.data.*.name` is the list of names).
 * AssertableInertia's own `where()` has no wildcards, so use this to compare whole columns of a list.
 *
 * @param  TestResponse<Response>  $response
 */
function pageProp(TestResponse $response, string $path): mixed
{
    return data_get($response->inertiaProps(), $path);
}

/**
 * The page props once every deferred group has loaded: the first response merged with the deferred request(s), as the client ends up with them.
 *
 * @param  TestResponse<Response>  $response
 * @return array<string, mixed>
 */
function loadedPageProps(TestResponse $response): array
{
    $deferred = [];

    // A plain closure, not an arrow function: an arrow function captures `$deferred` by value.
    $response->assertInertia(function (AssertableInertia $page) use (&$deferred) {
        $page->loadDeferredProps(function (AssertableInertia $reloaded) use (&$deferred) {
            $deferred = $reloaded->toArray()['props'];
        });
    });

    return [...$response->inertiaProps(), ...$deferred];
}

/**
 * Like pageProp(), on loadedPageProps(): a prop that arrives deferred, by dot path (supports `a.*.b` via data_get).
 *
 * @param  TestResponse<Response>  $response
 */
function loadedPageProp(TestResponse $response, string $path): mixed
{
    return data_get(loadedPageProps($response), $path);
}

/**
 * The SKU of each demo product by the number the plan and the fixtures give it (1 = Denim Jacket ... 7 = Leather Belt).
 * Database ids are not those numbers (a PostgreSQL sequence never goes back), so tests resolve them with `pid()`.
 */
const DEMO_PRODUCT_SKUS = [1 => 'PRD-JKT-001', 2 => 'PRD-TSH-002', 3 => 'PRD-SNK-003', 4 => 'PRD-TOT-004', 5 => 'PRD-JNS-005', 6 => 'PRD-JKT-006', 7 => 'PRD-BLT-007'];

/** The product (non-deleted) with this SKU: the one `DemoProductsSeeder` created, or one a test made. */
function demoProduct(string $sku): Product
{
    return Product::query()->where('sku', $sku)->firstOrFail();
}

/** The database id of the demo product with this number (1 = Denim Jacket ... 7 = Leather Belt). */
function pid(int $number): int
{
    return demoProduct(DEMO_PRODUCT_SKUS[$number])->id;
}

/**
 * The database id of the demo sale with this number (1 to 60, the order of DemoSalesSeeder::SALES; the oldest is 1). Assumes the
 * demo sales are the only ones: ids are not those numbers (a PostgreSQL sequence never goes back).
 */
function demoSaleId(int $number): int
{
    return (int) Sale::query()->orderBy('id')->offset($number - 1)->value('id');
}

/** An id no product has, however many a long test run created (the id of a PostgreSQL sequence only grows). */
const NO_SUCH_ID = 900000000;

/**
 * A valid Store/UpdateProductRequest payload: the Denim Jacket composition on the seeded materials, with overrides.
 * Needs the seeded materials (`prepareDemo()`); the SKU is new, so it also passes the create rules.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validProductPayload(array $overrides = []): array
{
    return [
        'sku' => 'PRD-NEW-100',
        'name' => 'Indigo Work Shirt',
        'category' => 'apparel',
        'status' => 'draft',
        'description' => 'A new shirt.',
        'labor_cost' => '8.5',
        'overhead_cost' => '2',
        'selling_price' => '59.9',
        'components' => [
            ['material_id' => demoMaterial('MAT-DEN-012')->id, 'quantity' => '1.6', 'waste_percent' => '8', 'notes' => 'Body'],
            ['material_id' => demoMaterial('MAT-THR-PES')->id, 'quantity' => '90', 'waste_percent' => '5', 'notes' => 'Seams'],
        ],
        ...$overrides,
    ];
}

/**
 * A valid Store/UpdateSaleRequest payload (sells the active Denim Jacket today: needs the demo products), with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validSalePayload(array $overrides = []): array
{
    return [
        'product_id' => pid(1),
        'quantity' => '3',
        'unit_price' => '89',
        'sold_on' => '2026-10-08',
        'note' => 'Web shop',
        ...$overrides,
    ];
}

/**
 * A valid Store/UpdateOrderRequest payload, with overrides: Boutique Lina's order of the card (Denim Jacket 50, Denim Jeans 5-pocket 30).
 * Needs the demo products (`prepareDemo()`). The order date is today (the frozen clock), so it is never in the future.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validOrderPayload(array $overrides = []): array
{
    return [
        'client' => 'Boutique Lina',
        'ordered_on' => now()->toDateString(),
        'note' => 'Deliver before 30 Oct, in 2 boxes',
        'lines' => [
            ['product_id' => pid(1), 'quantity' => '50'],
            ['product_id' => pid(5), 'quantity' => '30'],
        ],
        ...$overrides,
    ];
}

/**
 * A valid StoreRestockRequest payload, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validRestockPayload(array $overrides = []): array
{
    return [
        'quantity' => '500',
        'unit_price' => '0.36',
        'received_on' => '2026-10-08',
        'reference' => 'PO-2300',
        'update_unit_cost' => '1',
        ...$overrides,
    ];
}

/**
 * A real file on disk, sent under the given name and client MIME type. UploadedFile::fake() derives the type from the file
 * name, so only a real file proves that a photo rule looks at the content.
 */
function realUpload(string $name, string $content, string $clientMimeType): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'invory-photo-');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, $clientMimeType, null, true);
}

/*
|--------------------------------------------------------------------------
| Query budgets and partial reloads (ADR-0009, S-20)
|--------------------------------------------------------------------------
*/

/**
 * The SQL of every query the callback runs, in order.
 *
 * @return list<string>
 */
function queryLog(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $callback();

        return array_map(static fn (array $entry): string => $entry['query'], DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
}

/** How many SQL queries the callback runs. */
function countQueries(Closure $callback): int
{
    return count(queryLog($callback));
}

/**
 * The SQL run by one partial reload of a page (`X-Inertia-Partial-Data: <only>`), sent through Inertia's own testing API.
 * The first visit (the response you pass in) is not counted. `$assertions` receives the partial response's props.
 *
 * @param  TestResponse<Response>  $firstVisit
 * @param  list<string>  $only
 * @return list<string>
 */
function partialReloadQueries(TestResponse $firstVisit, array $only, Closure $assertions): array
{
    return queryLog(fn () => $firstVisit->assertInertia(
        fn (AssertableInertia $page) => $page->reloadOnly($only, $assertions),
    ));
}

/**
 * The queries whose SQL matches the pattern (case-insensitive regex, without delimiters).
 *
 * @param  list<string>  $queries
 * @return list<string>
 */
function queriesMatching(array $queries, string $pattern): array
{
    return array_values(array_filter($queries, static fn (string $sql): bool => preg_match('/'.$pattern.'/i', $sql) === 1));
}

/*
|--------------------------------------------------------------------------
| Products and their composition (S-04a)
|--------------------------------------------------------------------------
*/

/**
 * The four materials of the worked example in the invory-costing-domain skill, created through the factory.
 *
 * @return array{denim: Material, button: Material, thread: Material, label: Material}
 */
function workedExampleMaterials(string $buttonStock = '900.0000'): array
{
    return [
        'denim' => Material::factory()->create([
            'sku' => 'MAT-DEN-012', 'name' => 'Denim 12oz Indigo', 'category' => 'fabric', 'unit' => 'm',
            'unit_cost' => '8.5000', 'quantity_on_hand' => '250.0000',
        ]),
        'button' => Material::factory()->create([
            'sku' => 'MAT-BTN-017', 'name' => 'Metal shank button 17mm', 'category' => 'fastener', 'unit' => 'pc',
            'unit_cost' => '0.3500', 'quantity_on_hand' => $buttonStock,
        ]),
        'thread' => Material::factory()->create([
            'sku' => 'MAT-THR-PES', 'name' => 'Polyester thread', 'category' => 'thread', 'unit' => 'm',
            'unit_cost' => '0.0040', 'quantity_on_hand' => '20000.0000',
        ]),
        'label' => Material::factory()->create([
            'sku' => 'MAT-LBL-WVN', 'name' => 'Woven brand label', 'category' => 'label', 'unit' => 'pc',
            'unit_cost' => '0.1200', 'quantity_on_hand' => '400.0000',
        ]),
    ];
}

/**
 * The Denim Jacket composition of the worked example, as validated input for CreateProduct / SyncProductComponents.
 *
 * @param  array{denim: Material, button: Material, thread: Material, label: Material}  $materials
 * @return list<array{material_id: int, quantity: string, waste_percent: string, notes: string}>
 */
function workedExampleLines(array $materials): array
{
    return [
        ['material_id' => $materials['denim']->id, 'quantity' => '1.8000', 'waste_percent' => '10.00', 'notes' => 'Body'],
        ['material_id' => $materials['button']->id, 'quantity' => '8.0000', 'waste_percent' => '0.00', 'notes' => 'Front'],
        ['material_id' => $materials['thread']->id, 'quantity' => '120.0000', 'waste_percent' => '5.00', 'notes' => 'Seams'],
        ['material_id' => $materials['label']->id, 'quantity' => '1.0000', 'waste_percent' => '0.00', 'notes' => 'Neck'],
    ];
}

/**
 * Validated input for CreateProduct / UpdateProduct: the Denim Jacket (labor 12.0000, overhead 3.5000), with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productData(array $overrides = []): array
{
    return [
        'sku' => 'PRD-DJ-001',
        'name' => 'Denim Jacket',
        'category' => 'apparel',
        'status' => 'active',
        'description' => 'A classic.',
        'labor_cost' => '12.0000',
        'overhead_cost' => '3.5000',
        'selling_price' => null,
        'components' => [],
        ...$overrides,
    ];
}

/**
 * The Denim Jacket of the worked example, created through the CreateProduct Action and read back with its composition loaded.
 *
 * @param  array{denim: Material, button: Material, thread: Material, label: Material}  $materials
 * @param  array<string, mixed>  $overrides
 */
function createDenimJacket(array $materials, array $overrides = [], ?User $actor = null): Product
{
    $product = app(CreateProduct::class)->handle(
        productData(['components' => workedExampleLines($materials), ...$overrides]),
        $actor ?? User::factory()->create(),
    );

    return Product::query()->with('components.material')->findOrFail($product->id);
}

/**
 * `workshop_id` for a raw DB::table() insert (S-44-b): the oldest workshop, created when there is none. Empty when the table has no
 * such column, because a test rolled the migration that adds it back: the same row helper then also serves the older shape.
 *
 * @return array<string, int>
 */
function rawWorkshop(string $table): array
{
    if (! Schema::hasColumn($table, 'workshop_id')) {
        return [];
    }

    return ['workshop_id' => DB::table('workshops')->orderBy('id')->value('id') ?? DB::table('workshops')->insertGetId(['created_at' => now(), 'updated_at' => now()])];
}

/**
 * The orders migration object (S-48-a). It depends on `(workshop_id, id)` of `products`, so a test that rolls an older migration back
 * (the workshop columns, or anything below) must roll this one back first, newest first as `migrate:rollback` does, and bring it back up after.
 */
function ordersMigration(): Migration
{
    return require database_path('migrations/2026_10_15_200000_create_orders_and_order_lines_tables.php');
}

/**
 * The stage counts migration object (S-49-a): three columns on `order_lines`. It is newer than the orders one, so a test that rolls the
 * orders migration (or anything below) back must roll this one back first, and bring it back up after the orders one (the hand-kept list, F-60).
 */
function stageCountsMigration(): Migration
{
    return require database_path('migrations/2026_10_16_100000_add_stage_counts_to_order_lines.php');
}

/**
 * An order of the current workshop with its lines (S-48-b). `$lines` is a list of `[Product, quantity]` in the order they are entered
 * (the position follows the list); by default Boutique Lina's order of the card: Denim Jacket 50 then Denim Jeans 5-pocket 30 (needs the
 * demo products). The lines are created last-first, so their ids run against their positions: a read that sorted by id would show.
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<array{Product, int}>|null  $lines
 */
function bookOrder(array $attributes = [], ?array $lines = null): Order
{
    $lines ??= [[demoProduct('PRD-JKT-001'), 50], [demoProduct('PRD-JNS-005'), 30]];
    $order = Order::factory()->create([
        'client' => 'Boutique Lina', 'ordered_on' => '2026-10-15', 'note' => 'Deliver before 30 Oct, in 2 boxes', ...$attributes,
    ]);

    foreach (array_reverse($lines, true) as $position => [$product, $quantity]) {
        OrderLine::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $quantity, 'position' => $position]);
    }

    return $order;
}

/**
 * Boutique Lina's order with its lines at given stages (S-49): Denim Jacket then Denim Jeans 5-pocket (needs the demo products), each
 * `[quantity, in_cutting, ready_to_ship, shipped]`; To start is what is left. Like `bookOrder()`, the lines are created last-first.
 *
 * @param  list<array{int, int, int, int}>  $stages
 */
function bookOrderAtStages(array $stages): Order
{
    $products = [demoProduct('PRD-JKT-001'), demoProduct('PRD-JNS-005')];
    $order = Order::factory()->create(['client' => 'Boutique Lina', 'ordered_on' => '2026-10-15', 'note' => 'Deliver before 30 Oct, in 2 boxes']);

    foreach (array_reverse($stages, true) as $position => [$quantity, $inCutting, $readyToShip, $shipped]) {
        OrderLine::factory()->stages($inCutting, $readyToShip, $shipped)->create([
            'order_id' => $order->id, 'product_id' => $products[$position]->id, 'quantity' => $quantity, 'position' => $position,
        ]);
    }

    return $order;
}

/**
 * Every figure an order must not touch: the Denim Jacket's capacity (112), the materials, their history, the sales and the dashboard.
 * Compare two snapshots with `toBe()`. Needs the demo (`prepareDemo()`) and the signed-in `$this->user` of the test.
 *
 * @return array<string, mixed>
 */
function figuresOrdersLeaveAlone(): array
{
    $dashboard = loadedPageProps(test()->actingAs(test()->user)->get(route('dashboard'))->assertOk());

    return [
        'can produce' => loadedPageProp(test()->actingAs(test()->user)->get(route('products.show', pid(1)))->assertOk(), 'product.capacity.max_producible'),
        'materials' => Material::query()->orderBy('id')->get()->toArray(),
        'stock movements' => StockMovement::query()->orderBy('id')->get()->toArray(),
        'sales' => Sale::query()->orderBy('id')->get()->toArray(),
        'dashboard' => Arr::only($dashboard, ['stats', 'top_products', 'needs_restock', 'recent_sales', 'alerts']),
    ];
}
