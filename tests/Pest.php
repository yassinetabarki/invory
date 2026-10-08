<?php

use App\Models\Material;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
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
| UI-first preview helpers (ADR-0006)
|--------------------------------------------------------------------------
|
| The preview tests run with the clock frozen at 2026-10-08 12:00 and the 12 DemoCatalogSeeder materials in the
| database, so every "Expected" value of docs/plans/phase-2-ui-first-preview.md can be asserted exactly.
|
*/

/** The instant the preview fixture's `days_ago` values and the plan's "Expected" tables are relative to. */
const PREVIEW_NOW = '2026-10-08 12:00:00';

/**
 * Freeze the clock at PREVIEW_NOW, seed the 12 demo materials and return a verified user.
 * Call it from a `beforeEach` of a preview test file: `beforeEach(fn () => $this->user = preparePreview($this));`.
 */
function preparePreview(TestCase $test): User
{
    $test->travelTo(CarbonImmutable::parse(PREVIEW_NOW));
    $test->seed(DemoCatalogSeeder::class);

    return User::factory()->create();
}

/** The seeded (or factory-made) material with this SKU. */
function previewMaterial(string $sku): Material
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
 * A valid Store/UpdateProductRequest payload: the Denim Jacket composition on the seeded materials, with overrides.
 * Needs the seeded materials (`preparePreview()`); the SKU is new, so it also passes the create rules.
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
            ['material_id' => previewMaterial('MAT-DEN-012')->id, 'quantity' => '1.6', 'waste_percent' => '8', 'notes' => 'Body'],
            ['material_id' => previewMaterial('MAT-THR-PES')->id, 'quantity' => '90', 'waste_percent' => '5', 'notes' => 'Seams'],
        ],
        ...$overrides,
    ];
}

/**
 * A valid Store/UpdateSaleRequest payload (sells the active Denim Jacket today), with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validSalePayload(array $overrides = []): array
{
    return [
        'product_id' => 1,
        'quantity' => '3',
        'unit_price' => '89',
        'sold_on' => '2026-10-08',
        'note' => 'Web shop',
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
