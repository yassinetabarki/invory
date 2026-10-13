<?php

use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Materials on the preview fixture (ADR-0006, S-13c, S-04b)
|--------------------------------------------------------------------------
|
| The materials are real rows (DemoCatalogSeeder) and so are the products that use them: the used-in counts read the real
| compositions, "what you can make with it" is MaterialUsageTest, and the history is the real ledger (StockHistoryPropsTest). A
| photo is a path on the public disk (S-15-BE): the demo materials have none (D-6). Time frozen at 2026-10-08.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

describe('index', function () {
    it('sends the plan\'s used-in count on every row', function () {
        $response = actingAs($this->user)->get(route('materials.index'));

        expect(array_combine(pageProp($response, 'materials.data.*.sku'), pageProp($response, 'materials.data.*.used_in_count')))->toBe([
            'MAT-RVT-009' => 3,
            'MAT-CAN-010' => 2,
            'MAT-JRS-180' => 1,
            'MAT-DEN-012' => 2,
            'MAT-BTN-017' => 3,
            'MAT-ZIP-018' => 2,
            'MAT-LTH-NAP' => 2,
            'MAT-THR-PES' => 6,
            'MAT-SOL-042' => 1,
            'MAT-LIN-VIS' => 1,
            'MAT-LAC-120' => 1,
            'MAT-LBL-WOV' => 6,
        ]);
    });

    it('sends the public-disk URL of a photo, and null for the demo materials (D-6)', function () {
        demoMaterial('MAT-DEN-012')->forceFill(['photo_path' => 'materials/denim.jpg'])->save();

        $response = actingAs($this->user)->get(route('materials.index'));
        $photos = array_combine(pageProp($response, 'materials.data.*.sku'), pageProp($response, 'materials.data.*.photo_url'));

        expect($photos['MAT-DEN-012'])->toBe(Storage::disk('public')->url('materials/denim.jpg'))
            ->and(array_filter($photos))->toHaveCount(1);
    });

    it('sends the inventory value of the plan over all 12 materials', function () {
        actingAs($this->user)->get(route('materials.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary', ['materials_count' => 12, 'inventory_value' => '10544.3000'])
                ->has('materials.data', 12)
                ->has('materials.data.0', fn (Assert $material) => $material->hasAll(materialContractKeys())));
    });

    it('counts the usage of a material that is not in the fixture as zero, with no photo', function () {
        $stranger = Material::factory()->create(['sku' => 'ZZZ-OTHER', 'name' => 'Zzz other']);

        $response = actingAs($this->user)->get(route('materials.index', ['search' => 'zzz']));

        expect(pageProp($response, 'materials.data.0.id'))->toBe($stranger->id)
            ->and(pageProp($response, 'materials.data.0.used_in_count'))->toBe(0)
            ->and(pageProp($response, 'materials.data.0.photo_url'))->toBeNull();
    });

    it('serves the rows of a filtered or paginated list too', function () {
        config(['invory.per_page' => 5]);

        $response = actingAs($this->user)->get(route('materials.index', ['page' => 3, 'sort' => '-name']));

        // Sorted by name descending: the third page holds the last two (Cotton canvas, Brass rivet).
        expect(pageProp($response, 'materials.data.*.sku'))->toBe(['MAT-CAN-010', 'MAT-RVT-009'])
            ->and(pageProp($response, 'materials.data.*.used_in_count'))->toBe([2, 3])
            ->and(pageProp($response, 'materials.data.*.photo_url'))->toBe([null, null]);
    });

    it('answers within a bounded number of queries, whatever the number of materials on the page', function () {
        $queries = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            actingAs($this->user)->get(route('materials.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $baseline = $queries();

        // 12 more rows on the same page: still no query per row (strict mode would also throw on a lazy load).
        Material::factory()->count(12)->create();

        // Count, rows, their categories and units (eager), the inventory scan, and the reference lists of a full load
        // (the category option query is gone since S-22).
        expect($baseline)->toBeLessThanOrEqual(5 + REFERENCE_QUERIES)
            ->and($queries())->toBe($baseline);
    });
});

describe('show', function () {
    it('sends the MaterialShowProps contract', function () {
        DemoHistory::seed();

        actingAs($this->user)->get(route('materials.show', demoMaterial('MAT-BTN-017')))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('materials/show')
                ->hasAll(['material', 'usage', 'history'])
                ->has('material', fn (Assert $material) => $material->hasAll(materialContractKeys()))
                ->has('usage', 3, fn (Assert $row) => $row->hasAll([
                    'product', 'effective_quantity', 'units_covered', 'max_producible', 'bottleneck', 'is_bottleneck', 'next_limit',
                ]))
                ->has('usage.0.product', fn (Assert $product) => $product->hasAll(['id', 'sku', 'name', 'photo_url', 'status', 'status_label']))
                ->has('history', 4));
    });

    it('sends the material with its used-in count and the URL of its photo', function () {
        demoMaterial('MAT-BTN-017')->forceFill(['photo_path' => 'materials/button.webp'])->save();

        actingAs($this->user)->get(route('materials.show', demoMaterial('MAT-BTN-017')))
            ->assertInertia(fn (Assert $page) => $page
                ->where('material.sku', 'MAT-BTN-017')
                ->where('material.used_in_count', 3)
                ->where('material.photo_url', Storage::disk('public')->url('materials/button.webp'))
                ->where('material.supplier_name', 'Northgate Trims')
                ->where('material.supplier_reference', 'NT-17-SHK-AB')
                ->where('material.color', 'Antique brass')
                ->where('material.composition', 'Zinc alloy')
                ->where('material.notes', 'Hammer-on shank. Needs the tack-button press.'));
    });

    it('sends no usage and no history for a material that is not in the fixture', function () {
        $material = Material::factory()->create(['sku' => 'ZZZ-OTHER']);

        actingAs($this->user)->get(route('materials.show', $material))
            ->assertInertia(fn (Assert $page) => $page
                ->where('usage', [])
                ->where('history', [])
                ->where('material.used_in_count', 0)
                ->where('material.photo_url', null));
    });

    it('answers 404 for an unknown or deleted material', function () {
        $deleted = demoMaterial('MAT-LAC-120');
        $deleted->delete();

        actingAs($this->user)->get(route('materials.show', 999999))->assertNotFound();
        actingAs($this->user)->get(route('materials.show', $deleted->id))->assertNotFound();
    });
});

describe('edit', function () {
    it('sends the material with its used-in count and no photo for the form', function () {
        actingAs($this->user)->get(route('materials.edit', demoMaterial('MAT-DEN-012')))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('materials/edit')
                ->has('material', fn (Assert $material) => $material->hasAll(materialContractKeys()))
                ->where('material.used_in_count', 2)
                ->where('material.photo_url', null));
    });
});

it('reads the used-in count and the photo URL of the model in the MaterialResource itself', function () {
    $material = Material::factory()->make(['photo_path' => 'materials/p.jpg']);
    $material->setAttribute('used_in_count', 7);
    $material->created_at = now();
    $material->updated_at = now();

    $array = (new MaterialResource($material))->toArray(request());

    expect($array)->toHaveKeys(materialContractKeys())
        ->and($array['used_in_count'])->toBe(7)
        ->and($array['photo_url'])->toBe(Storage::disk('public')->url('materials/p.jpg'));
});
