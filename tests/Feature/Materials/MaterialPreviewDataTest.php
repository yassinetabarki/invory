<?php

use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Materials on the preview fixture (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| The materials are real rows (DemoCatalogSeeder); what has no table yet (used-in counts, photos, "what you can make with it",
| stock history) is added from App\Mock and costed by the production domain code. Time frozen at 2026-10-08.
*/

beforeEach(fn () => $this->user = preparePreview($this));

/** A material reference as the contract sends it. */
function usageMaterialRef(string $sku): array
{
    $material = previewMaterial($sku);

    return ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code];
}

/** The product of a usage row: a product reference with its status. */
function usageProduct(int $id, string $sku, string $name, string $status, bool $photo = true): array
{
    return [
        'id' => $id,
        'sku' => $sku,
        'name' => $name,
        'photo_url' => $photo ? asset('mock/photos/'.strtolower($sku).'.svg') : null,
        'status' => $status,
        'status_label' => ucfirst($status),
    ];
}

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

    it('sends the swatch of each material, and null for the two without a photo', function () {
        $response = actingAs($this->user)->get(route('materials.index'));
        $photos = array_combine(pageProp($response, 'materials.data.*.sku'), pageProp($response, 'materials.data.*.photo_url'));

        expect($photos['MAT-DEN-012'])->toBe(asset('mock/photos/mat-den-012.svg'))
            ->and($photos['MAT-LBL-WOV'])->toBe(asset('mock/photos/mat-lbl-wov.svg'))
            ->and($photos['MAT-LIN-VIS'])->toBeNull()
            ->and($photos['MAT-LAC-120'])->toBeNull()
            ->and(array_filter($photos))->toHaveCount(10);
    });

    it('has a static swatch file for every photo it links to', function () {
        $response = actingAs($this->user)->get(route('materials.index'));

        foreach (array_filter(pageProp($response, 'materials.data.*.photo_url')) as $url) {
            expect(public_path(str_replace(asset(''), '', $url)))->toBeFile();
        }
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

    it('decorates the rows of a filtered or paginated list too', function () {
        config(['invory.per_page' => 5]);

        $response = actingAs($this->user)->get(route('materials.index', ['page' => 3, 'sort' => '-name']));

        // Sorted by name descending: the third page holds the last two (Cotton canvas, Brass rivet).
        expect(pageProp($response, 'materials.data.*.sku'))->toBe(['MAT-CAN-010', 'MAT-RVT-009'])
            ->and(pageProp($response, 'materials.data.*.used_in_count'))->toBe([2, 3])
            ->and(pageProp($response, 'materials.data.*.photo_url'))->toBe([asset('mock/photos/mat-can-010.svg'), asset('mock/photos/mat-rvt-009.svg')]);
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

        // Count, rows, their categories and units (eager), the inventory scan, and the reference lists of a full load.
        expect($baseline)->toBeLessThanOrEqual(5 + 2 + REFERENCE_QUERIES)
            ->and($queries())->toBe($baseline);
    });

    describe('deferred recent history', function () {
        it('is not part of the first response', function () {
            actingAs($this->user)->get(route('materials.index'))
                ->assertInertia(fn (Assert $page) => $page->missing('recent_history')->has('materials.data', 12));
        });

        it('loads the newest movements of every material on the page, material by material', function () {
            actingAs($this->user)->get(route('materials.index'))
                ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                    $reloaded->has('recent_history', 26, fn (Assert $movement) => $movement->hasAll([
                        'id', 'material_id', 'type', 'type_label', 'occurred_on', 'quantity', 'quantity_before', 'quantity_after',
                        'unit_price', 'unit_cost_before', 'unit_cost_after', 'reference', 'note', 'user_name',
                    ]));
                }));
        });

        it('keeps the order of the materials and puts the newest entry of each first', function () {
            $response = actingAs($this->user)->get(route('materials.index'));

            $response->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                $props = $reloaded->toArray()['props'];

                // The materials by name: rivet, canvas, jersey, denim, button, zipper, nappa, thread, cupsole, lining, laces, label.
                expect(array_column($props['recent_history'], 'id'))->toBe([
                    2, 1, 4, 3, 6, 5, 8, 7, 12, 11, 10, 9, 14, 13, 16, 15, 18, 17, 20, 19, 21, 23, 22, 26, 25, 24,
                ])->and(array_column($props['recent_history'], 'material_id'))->toBe(array_map(
                    fn (string $sku): int => previewMaterial($sku)->id,
                    [
                        'MAT-RVT-009', 'MAT-RVT-009', 'MAT-CAN-010', 'MAT-CAN-010', 'MAT-JRS-180', 'MAT-JRS-180', 'MAT-DEN-012', 'MAT-DEN-012',
                        'MAT-BTN-017', 'MAT-BTN-017', 'MAT-BTN-017', 'MAT-BTN-017', 'MAT-ZIP-018', 'MAT-ZIP-018', 'MAT-LTH-NAP', 'MAT-LTH-NAP',
                        'MAT-THR-PES', 'MAT-THR-PES', 'MAT-SOL-042', 'MAT-SOL-042', 'MAT-LIN-VIS', 'MAT-LAC-120', 'MAT-LAC-120',
                        'MAT-LBL-WOV', 'MAT-LBL-WOV', 'MAT-LBL-WOV',
                    ],
                ));
            }));
        });

        it('only covers the materials of the filtered page', function () {
            actingAs($this->user)->get(route('materials.index', ['category' => 'thread']))
                ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                    expect(array_column($reloaded->toArray()['props']['recent_history'], 'id'))->toBe([18, 17]);
                }));
        });

        it('is empty for a page of materials with no history', function () {
            Material::factory()->create(['sku' => 'ZZZ-NEW', 'name' => 'Zzz new']);

            actingAs($this->user)->get(route('materials.index', ['search' => 'zzz new']))
                ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                    expect($reloaded->toArray()['props']['recent_history'])->toBe([]);
                }));
        });
    });
});

describe('show', function () {
    it('sends the MaterialShowProps contract', function () {
        actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')))
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

    it('sends the material with its used-in count and photo', function () {
        actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')))
            ->assertInertia(fn (Assert $page) => $page
                ->where('material.sku', 'MAT-BTN-017')
                ->where('material.used_in_count', 3)
                ->where('material.photo_url', asset('mock/photos/mat-btn-017.svg'))
                ->where('material.supplier_name', 'Northgate Trims')
                ->where('material.supplier_reference', 'NT-17-SHK-AB')
                ->where('material.color', 'Antique brass')
                ->where('material.composition', 'Zinc alloy')
                ->where('material.notes', 'Hammer-on shank. Needs the tack-button press.'));
    });

    it('sends the usage of the button: the bottleneck row first, then by product name', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')));

        expect(pageProp($response, 'usage'))->toBe([
            [
                'product' => usageProduct(1, 'PRD-JKT-001', 'Denim Jacket', 'active'),
                'effective_quantity' => '8.0000',
                'units_covered' => 112,
                'max_producible' => 112,
                'bottleneck' => usageMaterialRef('MAT-BTN-017'),
                'is_bottleneck' => true,
                'next_limit' => ['material' => usageMaterialRef('MAT-DEN-012'), 'max_producible' => 126],
            ],
            [
                'product' => usageProduct(6, 'PRD-JKT-006', 'Canvas Chore Jacket', 'draft', photo: false),
                'effective_quantity' => '5.0000',
                'units_covered' => 180,
                'max_producible' => 129,
                'bottleneck' => usageMaterialRef('MAT-CAN-010'),
                'is_bottleneck' => false,
                'next_limit' => null,
            ],
            [
                'product' => usageProduct(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket', 'active'),
                'effective_quantity' => '1.0000',
                'units_covered' => 900,
                'max_producible' => 105,
                'bottleneck' => usageMaterialRef('MAT-THR-PES'),
                'is_bottleneck' => false,
                'next_limit' => null,
            ],
        ]);
    });

    it('sends the usage of the thread: Jeans first (its bottleneck, next limit denim at 165), then by name', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-THR-PES')));

        expect(pageProp($response, 'usage.*.product.name'))->toBe([
            'Denim Jeans 5-pocket', 'Canvas Chore Jacket', 'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket', 'Leather Tote',
        ])->and(pageProp($response, 'usage.*.is_bottleneck'))->toBe([true, false, false, false, false, false])
            // The thread per unit, waste included: 189, 136.5, 42, 84, 126 and 157.5 m.
            ->and(pageProp($response, 'usage.*.effective_quantity'))->toBe(['189.0000', '136.5000', '42.0000', '84.0000', '126.0000', '157.5000'])
            ->and(pageProp($response, 'usage.*.units_covered'))->toBe([105, 146, 476, 238, 158, 126])
            ->and(pageProp($response, 'usage.*.max_producible'))->toBe([105, 129, 0, 50, 112, 53])
            ->and(pageProp($response, 'usage.*.bottleneck.sku'))->toBe(['MAT-THR-PES', 'MAT-CAN-010', 'MAT-SOL-042', 'MAT-JRS-180', 'MAT-BTN-017', 'MAT-LTH-NAP'])
            ->and(pageProp($response, 'usage.0.next_limit'))->toBe(['material' => usageMaterialRef('MAT-DEN-012'), 'max_producible' => 165])
            ->and(pageProp($response, 'usage.*.next_limit'))->toHaveCount(6);
    });

    it('sends the cupsole as the bottleneck of the sneaker that can\'t be made, with the label as next limit', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-SOL-042')));

        expect(pageProp($response, 'usage'))->toBe([[
            'product' => usageProduct(3, 'PRD-SNK-003', 'Canvas Low Sneaker', 'active'),
            'effective_quantity' => '1.0000',
            'units_covered' => 0,
            'max_producible' => 0,
            'bottleneck' => usageMaterialRef('MAT-SOL-042'),
            'is_bottleneck' => true,
            'next_limit' => ['material' => usageMaterialRef('MAT-LBL-WOV'), 'max_producible' => 400],
        ]]);
    });

    it('has no next limit when the material is the only one of the composition', function () {
        // The Belt is nappa leather and brass rivets: without the rivets the leather is its only material.
        previewMaterial('MAT-RVT-009')->delete();

        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-LTH-NAP')));
        $belt = collect(pageProp($response, 'usage'))->firstWhere('product.id', 7);

        expect($belt['is_bottleneck'])->toBeTrue()
            ->and($belt['max_producible'])->toBe(391)
            ->and($belt['next_limit'])->toBeNull();
    });

    it('moves the next limit when another material of the composition goes away', function () {
        // Without denim the jacket's next limit after the button is the thread (158 units).
        previewMaterial('MAT-DEN-012')->delete();

        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')));

        expect(pageProp($response, 'usage.0.next_limit'))->toBe(['material' => usageMaterialRef('MAT-THR-PES'), 'max_producible' => 158]);
    });

    it('follows the stock immediately', function () {
        // 7 buttons: the button now limits all three products (0 Denim Jackets at 8 per unit, 1 Chore Jacket at 5, 7 Jeans at 1),
        // so every row is a bottleneck row and they are listed by name.
        previewMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);

        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')));

        expect(pageProp($response, 'usage.*.product.id'))->toBe([6, 1, 5])
            ->and(pageProp($response, 'usage.*.units_covered'))->toBe([1, 0, 7])
            ->and(pageProp($response, 'usage.*.max_producible'))->toBe([1, 0, 7])
            ->and(pageProp($response, 'usage.*.is_bottleneck'))->toBe([true, true, true]);
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

    it('sends the history of the button, newest first, with every value of the plan', function () {
        $button = previewMaterial('MAT-BTN-017');
        $response = actingAs($this->user)->get(route('materials.show', $button));

        expect(pageProp($response, 'history'))->toBe([
            [
                'id' => 12,
                'material_id' => $button->id,
                'type' => 'price_change',
                'type_label' => 'Price change',
                'occurred_on' => '2026-10-02',
                'quantity' => null,
                'quantity_before' => null,
                'quantity_after' => null,
                'unit_price' => null,
                'unit_cost_before' => '0.3200',
                'unit_cost_after' => '0.3500',
                'reference' => null,
                'note' => null,
                'user_name' => 'Admin',
            ],
            [
                'id' => 11,
                'material_id' => $button->id,
                'type' => 'restock',
                'type_label' => 'Restock',
                'occurred_on' => '2026-10-02',
                'quantity' => '500.0000',
                'quantity_before' => null,
                'quantity_after' => '900.0000',
                'unit_price' => '0.3500',
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => 'PO-2291',
                'note' => null,
                'user_name' => 'Admin',
            ],
            [
                'id' => 10,
                'material_id' => $button->id,
                'type' => 'correction',
                'type_label' => 'Correction',
                'occurred_on' => '2026-09-14',
                'quantity' => null,
                'quantity_before' => '600.0000',
                'quantity_after' => '400.0000',
                'unit_price' => null,
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => null,
                'note' => 'Stock count',
                'user_name' => 'Admin',
            ],
            [
                'id' => 9,
                'material_id' => $button->id,
                'type' => 'created',
                'type_label' => 'Created',
                'occurred_on' => '2026-09-01',
                'quantity' => '600.0000',
                'quantity_before' => null,
                'quantity_after' => '600.0000',
                'unit_price' => '0.3200',
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => null,
                'note' => null,
                'user_name' => 'Admin',
            ],
        ]);
    });

    it('lists a same-day restock and price change newest entry first (higher id)', function () {
        $label = previewMaterial('MAT-LBL-WOV');
        $response = actingAs($this->user)->get(route('materials.show', $label));

        expect(pageProp($response, 'history.*.id'))->toBe([26, 25, 24])
            ->and(pageProp($response, 'history.*.type'))->toBe(['price_change', 'restock', 'created'])
            ->and(pageProp($response, 'history.*.occurred_on'))->toBe(['2026-09-24', '2026-09-24', '2026-09-01'])
            ->and(pageProp($response, 'history.0'))->toMatchArray(['unit_cost_before' => '0.1000', 'unit_cost_after' => '0.1200'])
            ->and(pageProp($response, 'history.1'))->toMatchArray(['quantity' => '150.0000', 'quantity_after' => '400.0000', 'unit_price' => '0.1200', 'reference' => null]);
    });

    it('has only the opening entry for a material that was never touched', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-LIN-VIS')));

        expect(pageProp($response, 'history'))->toHaveCount(1)
            ->and(pageProp($response, 'history.0'))->toMatchArray([
                'id' => 21,
                'type' => 'created',
                'quantity' => '180.0000',
                'quantity_after' => '180.0000',
                'unit_price' => '3.1000',
                'occurred_on' => '2026-09-01',
            ]);
    });

    it('sends every material\'s history by the plan\'s entry counts', function (string $sku, int $entries, array $types) {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial($sku)));

        expect(pageProp($response, 'history'))->toHaveCount($entries)
            ->and(pageProp($response, 'history.*.type'))->toBe($types);
    })->with([
        'rivet' => ['MAT-RVT-009', 2, ['restock', 'created']],
        'canvas' => ['MAT-CAN-010', 2, ['restock', 'created']],
        'jersey' => ['MAT-JRS-180', 2, ['correction', 'created']],
        'denim' => ['MAT-DEN-012', 2, ['restock', 'created']],
        'button' => ['MAT-BTN-017', 4, ['price_change', 'restock', 'correction', 'created']],
        'zipper' => ['MAT-ZIP-018', 2, ['restock', 'created']],
        'nappa' => ['MAT-LTH-NAP', 2, ['restock', 'created']],
        'thread' => ['MAT-THR-PES', 2, ['restock', 'created']],
        'cupsole' => ['MAT-SOL-042', 2, ['correction', 'created']],
        'lining' => ['MAT-LIN-VIS', 1, ['created']],
        'laces' => ['MAT-LAC-120', 2, ['restock', 'created']],
        'label' => ['MAT-LBL-WOV', 3, ['price_change', 'restock', 'created']],
    ]);

    it('serialises the seed values the history started from', function () {
        // A correction records the quantities before and after, never a unit price.
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-SOL-042')));

        expect(pageProp($response, 'history.0'))->toMatchArray([
            'id' => 20,
            'type' => 'correction',
            'quantity_before' => '60.0000',
            'quantity_after' => '0.0000',
            'unit_price' => null,
            'note' => 'Used in production',
            'occurred_on' => '2026-09-30',
        ]);
    });

    it('answers 404 for an unknown or deleted material', function () {
        $deleted = previewMaterial('MAT-LAC-120');
        $deleted->delete();

        actingAs($this->user)->get(route('materials.show', 999999))->assertNotFound();
        actingAs($this->user)->get(route('materials.show', $deleted->id))->assertNotFound();
    });
});

describe('edit', function () {
    it('sends the material with its used-in count and photo for the form', function () {
        actingAs($this->user)->get(route('materials.edit', previewMaterial('MAT-DEN-012')))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('materials/edit')
                ->has('material', fn (Assert $material) => $material->hasAll(materialContractKeys()))
                ->where('material.used_in_count', 2)
                ->where('material.photo_url', asset('mock/photos/mat-den-012.svg')));
    });
});

it('does not depend on the preview data in the MaterialResource itself', function () {
    // The resource reads the two attributes the controller sets: this is what the real withCount() and accessor will fill.
    $material = Material::factory()->make();
    $material->setAttribute('used_in_count', 7)->setAttribute('photo_url', 'https://example.test/p.jpg');
    $material->created_at = now();
    $material->updated_at = now();

    $array = (new MaterialResource($material))->toArray(request());

    expect($array)->toHaveKeys(materialContractKeys())
        ->and($array['used_in_count'])->toBe(7)
        ->and($array['photo_url'])->toBe('https://example.test/p.jpg');
});
