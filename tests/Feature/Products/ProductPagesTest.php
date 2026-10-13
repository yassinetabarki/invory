<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Products pages on the real tables (ADR-0006, S-13c, S-04b)
|--------------------------------------------------------------------------
|
| Time frozen at 2026-10-08, DemoCatalogSeeder run (the 12 materials and the 7 demo products). Every derived number is computed
| by the production domain code; these tests pin the "Expected" tables of docs/plans/phase-2-ui-first-preview.md exactly, which
| is the proof that the pages send the same props as before the switch from the fixture to the tables.
|
| `$id` in the datasets is the number of the plan (1 = Denim Jacket ... 7 = Leather Belt): `pid()` gives the database id.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * The plan's "Expected" products table:
 * [id, sku, name, category, status, selling price, material cost, unit cost, max producible, bottleneck SKU,
 *  margin amount, margin percent, components, sold in the last 30 days].
 */
dataset('expectedProducts', [
    'Denim Jacket' => [1, 'PRD-JKT-001', 'Denim Jacket', 'apparel', 'active', '89.0000', '20.2540', '35.7540', 112, 'MAT-BTN-017', '53.2460', '59.83', 4, 38],
    'Classic White T-shirt' => [2, 'PRD-TSH-002', 'Classic White T-shirt', 'apparel', 'active', '24.0000', '5.7984', '9.7484', 50, 'MAT-JRS-180', '14.2516', '59.38', 3, 142],
    'Canvas Low Sneaker' => [3, 'PRD-SNK-003', 'Canvas Low Sneaker', 'footwear', 'active', '69.0000', '10.4990', '21.7990', 0, 'MAT-SOL-042', '47.2010', '68.41', 5, 9],
    'Leather Tote' => [4, 'PRD-TOT-004', 'Leather Tote', 'bag', 'active', '249.0000', '73.7600', '101.0300', 53, 'MAT-LTH-NAP', '147.9700', '59.43', 6, 12],
    'Denim Jeans 5-pocket' => [5, 'PRD-JNS-005', 'Denim Jeans 5-pocket', 'apparel', 'active', '79.0000', '15.5080', '29.4780', 105, 'MAT-THR-PES', '49.5220', '62.69', 6, 64],
    'Canvas Chore Jacket' => [6, 'PRD-JKT-006', 'Canvas Chore Jacket', 'apparel', 'draft', null, '14.8900', '32.4300', 129, 'MAT-CAN-010', null, null, 4, 0],
    'Leather Belt' => [7, 'PRD-BLT-007', 'Leather Belt', 'accessory', 'active', '45.0000', '9.7040', '17.0440', 391, 'MAT-LTH-NAP', '27.9560', '62.12', 2, 31],
]);

function productsIndexUrl(array $query = []): string
{
    return route('products.index', $query);
}

describe('index', function () {
    it('sends the ProductsIndexProps contract', function () {
        actingAs($this->user)->get(productsIndexUrl())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/index')
                ->hasAll(['products', 'filters', 'statuses'])
                ->missing('categories')
                ->has('products.data', 7)
                ->has('products.links', fn (Assert $links) => $links->hasAll(['first', 'last', 'prev', 'next']))
                ->has('products.meta', fn (Assert $meta) => $meta
                    ->hasAll(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']))
                ->where('products.meta.total', 7)
                ->where('products.meta.per_page', 25)
                ->where('products.meta.path', route('products.index'))
                ->where('filters', ['search' => null, 'category' => null, 'status' => null, 'cant_produce' => false])
                ->where('statuses', [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'archived', 'label' => 'Archived'],
                ]));
    });

    it('lists the products by name with exactly the ProductListItem keys', function () {
        $response = actingAs($this->user)->get(productsIndexUrl());

        expect(pageProp($response, 'products.data.*.name'))->toBe([
            'Canvas Chore Jacket', 'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket',
            'Denim Jeans 5-pocket', 'Leather Belt', 'Leather Tote',
        ])->and(pageProp($response, 'products.data.*.id'))->toBe(array_map(pid(...), [6, 3, 2, 1, 5, 7, 4]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('products.data.0', fn (Assert $item) => $item->hasAll([
                'id', 'sku', 'name', 'category', 'category_label', 'status', 'status_label', 'photo_url',
                'components_count', 'unit_cost', 'selling_price', 'margin', 'capacity', 'units_sold_last_30_days',
            ])));
    });

    it('computes the plan\'s expected row of each product', function (
        int $id, string $sku, string $name, string $category, string $status, ?string $price, string $materialCost,
        string $unitCost, int $maxProducible, string $bottleneckSku, ?string $marginAmount, ?string $marginPercent,
        int $components, int $sold,
    ) {
        $bottleneck = demoMaterial($bottleneckSku);

        $response = actingAs($this->user)->get(productsIndexUrl(['search' => $sku]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->has('products.data.0', fn (Assert $item) => $item
                ->where('id', pid($id))
                ->where('sku', $sku)
                ->where('name', $name)
                ->where('category', $category)
                ->where('category_label', ucfirst($category))
                ->where('status', $status)
                ->where('status_label', ucfirst($status))
                ->where('components_count', $components)
                ->where('unit_cost', $unitCost)
                ->where('selling_price', $price)
                ->where('margin', $marginAmount === null ? null : ['amount' => $marginAmount, 'percent' => $marginPercent])
                ->where('capacity', [
                    'max_producible' => $maxProducible,
                    'bottleneck' => ['id' => $bottleneck->id, 'sku' => $bottleneckSku, 'name' => $bottleneck->name, 'unit' => $bottleneck->unit->code],
                ])
                ->where('units_sold_last_30_days', $sold)
                ->etc()));
    })->with('expectedProducts');

    it('serialises the public-disk URL of the photo of a product, and null for the draft without one', function () {
        demoProduct('PRD-JKT-001')->forceFill(['photo_path' => 'products/jacket.jpg'])->save();

        actingAs($this->user)->get(productsIndexUrl())
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.sku', 'PRD-JKT-006')
                ->where('products.data.0.photo_url', null)
                ->where('products.data.3.sku', 'PRD-JKT-001')
                ->where('products.data.3.photo_url', Storage::disk('public')->url('products/jacket.jpg')));
    });

    describe('filters', function () {
        it('searches name and SKU case-insensitively', function (string $search, array $names) {
            $response = actingAs($this->user)->get(productsIndexUrl(['search' => $search]));

            expect(pageProp($response, 'products.data.*.name'))->toBe($names)
                ->and(pageProp($response, 'filters.search'))->toBe(trim($search));
        })->with([
            'name, other case' => ['DENIM', ['Denim Jacket', 'Denim Jeans 5-pocket']],
            'name, lower case' => ['leather', ['Leather Belt', 'Leather Tote']],
            'sku, lower case' => ['prd-tsh', ['Classic White T-shirt']],
            'sku, full' => ['PRD-BLT-007', ['Leather Belt']],
            'trimmed' => ['  jacket  ', ['Canvas Chore Jacket', 'Denim Jacket']],
            'no match' => ['zzz', []],
        ]);

        it('filters by category', function (string $category, array $names) {
            $response = actingAs($this->user)->get(productsIndexUrl(['category' => $category]));

            expect(pageProp($response, 'products.data.*.name'))->toBe($names)
                ->and(pageProp($response, 'filters.category'))->toBe($category);
        })->with([
            'apparel' => ['apparel', ['Canvas Chore Jacket', 'Classic White T-shirt', 'Denim Jacket', 'Denim Jeans 5-pocket']],
            'footwear' => ['footwear', ['Canvas Low Sneaker']],
            'bag' => ['bag', ['Leather Tote']],
            'accessory' => ['accessory', ['Leather Belt']],
            'jewelry' => ['jewelry', []],
        ]);

        it('filters by status', function (string $status, array $names) {
            $response = actingAs($this->user)->get(productsIndexUrl(['status' => $status]));

            expect(pageProp($response, 'products.data.*.name'))->toBe($names)
                ->and(pageProp($response, 'filters.status'))->toBe($status);
        })->with([
            'draft' => ['draft', ['Canvas Chore Jacket']],
            'active' => ['active', ['Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket', 'Denim Jeans 5-pocket', 'Leather Belt', 'Leather Tote']],
            'archived' => ['archived', []],
        ]);

        it('lists only the products that can\'t be produced with cant_produce', function () {
            $response = actingAs($this->user)->get(productsIndexUrl(['cant_produce' => '1']));

            expect(pageProp($response, 'products.data.*.name'))->toBe(['Canvas Low Sneaker'])
                ->and(pageProp($response, 'products.data.0.capacity.max_producible'))->toBe(0)
                ->and(pageProp($response, 'filters.cant_produce'))->toBeTrue();
        });

        it('treats cant_produce=0 and an empty value as off', function (string $value) {
            actingAs($this->user)->get(productsIndexUrl(['cant_produce' => $value]))
                ->assertInertia(fn (Assert $page) => $page
                    ->has('products.data', 7)
                    ->where('filters.cant_produce', false));
        })->with(['zero' => '0', 'empty' => '']);

        it('combines the filters', function () {
            $response = actingAs($this->user)->get(productsIndexUrl(['search' => 'jacket', 'category' => 'apparel', 'status' => 'active']));

            expect(pageProp($response, 'products.data.*.name'))->toBe(['Denim Jacket'])
                ->and(pageProp($response, 'filters'))->toBe(['search' => 'jacket', 'category' => 'apparel', 'status' => 'active', 'cant_produce' => false]);
        });

        it('does not count a product without a composition as "can\'t produce"', function () {
            // Every material of the Chore Jacket is gone: its composition is empty, so its capacity is null, not 0.
            Material::query()->whereIn('sku', ['MAT-CAN-010', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->delete();
            Material::query()->where('sku', 'MAT-LTH-NAP')->delete();

            $response = actingAs($this->user)->get(productsIndexUrl(['cant_produce' => '1']));

            expect(pageProp($response, 'products.data.*.name'))->toBe(['Canvas Low Sneaker']);
        });

        it('rejects invalid filters', function (array $query, string $field) {
            actingAs($this->user)->get(productsIndexUrl($query))->assertSessionHasErrors($field);
        })->with([
            'unknown category' => [['category' => 'bananas'], 'category'],
            'unknown status' => [['status' => 'bananas'], 'status'],
            'array search' => [['search' => ['a']], 'search'],
            'long search' => [['search' => str_repeat('a', 101)], 'search'],
            'non boolean cant_produce' => [['cant_produce' => 'maybe'], 'cant_produce'],
            'page zero' => [['page' => '0'], 'page'],
            'page text' => [['page' => 'abc'], 'page'],
        ]);
    });

    it('paginates like a paginated resource collection', function () {
        config(['invory.per_page' => 3]);

        actingAs($this->user)->get(productsIndexUrl(['category' => 'apparel', 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Denim Jeans 5-pocket')
                ->where('products.meta.current_page', 2)
                ->where('products.meta.last_page', 2)
                ->where('products.meta.per_page', 3)
                ->where('products.meta.from', 4)
                ->where('products.meta.to', 4)
                ->where('products.meta.total', 4)
                ->where('products.meta.path', route('products.index'))
                ->where('products.links.prev', fn (string $url) => str_contains($url, 'page=1') && str_contains($url, 'category=apparel'))
                ->where('products.links.next', null)
                ->has('products.meta.links', 4, fn (Assert $link) => $link->hasAll(['url', 'label', 'active'])->etc()));
    });

    it('keeps the query string in the pagination links', function () {
        config(['invory.per_page' => 3]);

        actingAs($this->user)->get(productsIndexUrl(['status' => 'active']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.links.next', fn (string $url) => str_contains($url, 'page=2') && str_contains($url, 'status=active')));
    });

    it('answers an empty page beyond the last one', function () {
        actingAs($this->user)->get(productsIndexUrl(['page' => 9]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 0)
                ->where('products.meta.total', 7)
                ->where('products.meta.current_page', 9));
    });
});

describe('show', function () {
    it('sends the ProductShowProps contract', function () {
        actingAs($this->user)->get(route('products.show', pid(1)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/show')
                ->hasAll(['product', 'sales_last_30_days', 'recent_sales'])
                ->has('product', fn (Assert $product) => $product->hasAll([
                    'id', 'sku', 'name', 'category', 'category_label', 'status', 'status_label', 'description', 'photo_url',
                    'labor_cost', 'overhead_cost', 'selling_price', 'cost', 'margin', 'capacity', 'components',
                    'created_at', 'updated_at',
                ]))
                ->has('product.cost', fn (Assert $cost) => $cost->hasAll(['material_cost', 'labor_cost', 'overhead_cost', 'unit_cost']))
                ->has('product.margin', fn (Assert $margin) => $margin->hasAll(['amount', 'percent']))
                ->has('product.capacity', fn (Assert $capacity) => $capacity->hasAll(['max_producible', 'bottleneck']))
                ->has('sales_last_30_days', fn (Assert $sales) => $sales->hasAll(['units_sold', 'revenue']))
                ->has('recent_sales', 5));
    });

    it('computes the plan\'s expected cost, capacity and margin of each product', function (
        int $id, string $sku, string $name, string $category, string $status, ?string $price, string $materialCost,
        string $unitCost, int $maxProducible, string $bottleneckSku, ?string $marginAmount, ?string $marginPercent,
        int $components, int $sold,
    ) {
        $bottleneck = demoMaterial($bottleneckSku);

        actingAs($this->user)->get(route('products.show', pid($id)))
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.id', pid($id))
                ->where('product.sku', $sku)
                ->where('product.name', $name)
                ->where('product.status', $status)
                ->where('product.selling_price', $price)
                ->where('product.cost.material_cost', $materialCost)
                ->where('product.cost.unit_cost', $unitCost)
                ->where('product.margin', $marginAmount === null ? null : ['amount' => $marginAmount, 'percent' => $marginPercent])
                ->where('product.capacity.max_producible', $maxProducible)
                ->where('product.capacity.bottleneck.sku', $bottleneckSku)
                ->where('product.capacity.bottleneck.name', $bottleneck->name)
                ->has('product.components', $components)
                ->where('sales_last_30_days.units_sold', $sold)
                ->etc());
    })->with('expectedProducts');

    it('matches the worked example of the costing domain skill for the Denim Jacket', function () {
        demoProduct('PRD-JKT-001')->forceFill(['photo_path' => 'products/jacket.jpg'])->save();

        $response = actingAs($this->user)->get(route('products.show', pid(1)));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('product.labor_cost', '12.0000')
            ->where('product.overhead_cost', '3.5000')
            ->where('product.cost', [
                'material_cost' => '20.2540',
                'labor_cost' => '12.0000',
                'overhead_cost' => '3.5000',
                'unit_cost' => '35.7540',
            ])
            ->where('product.description', 'Classic trucker jacket in 12oz indigo denim with metal shank buttons.')
            ->where('product.category_label', 'Apparel')
            ->where('product.status_label', 'Active')
            ->where('product.photo_url', Storage::disk('public')->url('products/jacket.jpg'))
            // The real timestamps of the row (the seeder ran at the frozen clock), as ISO-8601 strings.
            ->where('product.created_at', '2026-10-08T12:00:00+00:00')
            ->where('product.updated_at', '2026-10-08T12:00:00+00:00'));

        // The golden lines: effective quantity, line cost, units possible and the bottleneck of each of the 4 lines.
        expect(pageProp($response, 'product.components.*.effective_quantity'))->toBe(['1.9800', '8.0000', '126.0000', '1.0000'])
            ->and(pageProp($response, 'product.components.*.line_cost'))->toBe(['16.8300', '2.8000', '0.5040', '0.1200'])
            ->and(pageProp($response, 'product.components.*.units_possible'))->toBe([126, 112, 158, 400])
            ->and(pageProp($response, 'product.components.*.is_bottleneck'))->toBe([false, true, false, false]);
    });

    it('sends the composition lines with the ProductComponentLine contract, in position order', function () {
        $denim = demoMaterial('MAT-DEN-012');
        $denim->forceFill(['photo_path' => 'materials/denim.jpg'])->save();
        $response = actingAs($this->user)->get(route('products.show', pid(1)));

        // The ids are the real ids of the stored lines, in position order.
        $ids = ProductComponent::query()->where('product_id', pid(1))->orderBy('position')->pluck('id')->all();

        expect(pageProp($response, 'product.components.*.id'))->toBe($ids)
            ->and(pageProp($response, 'product.components.*.position'))->toBe([1, 2, 3, 4])
            ->and(pageProp($response, 'product.components.*.material.sku'))->toBe(['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])
            ->and(pageProp($response, 'product.components.*.notes'))->toBe(['Body, sleeves, collar', 'Front placket (6), cuffs (2)', 'All seams', 'Inside back yoke']);

        $response->assertInertia(fn (Assert $page) => $page
            ->has('product.components.0', fn (Assert $line) => $line
                ->where('id', $ids[0])
                ->where('position', 1)
                ->where('material', [
                    'id' => $denim->id,
                    'sku' => 'MAT-DEN-012',
                    'name' => 'Denim 12oz Indigo',
                    'unit' => 'm',
                    'photo_url' => Storage::disk('public')->url('materials/denim.jpg'),
                    'stock_status' => 'in_stock',
                    'reorder_level' => '100.0000',
                ])
                ->where('quantity', '1.8000')
                ->where('waste_percent', '10.00')
                ->where('effective_quantity', '1.9800')
                ->where('unit_cost', '8.5000')
                ->where('line_cost', '16.8300')
                ->where('stock_on_hand', '250.0000')
                ->where('units_possible', 126)
                ->where('is_bottleneck', false)
                ->where('notes', 'Body, sleeves, collar'))
            ->where('product.components.1.material.stock_status', 'low')
            ->where('product.components.1.notes', 'Front placket (6), cuffs (2)')
            ->where('product.components.1.quantity', '8.0000')
            ->where('product.components.1.waste_percent', '0.00')
            ->where('product.capacity.bottleneck.sku', 'MAT-BTN-017'));
    });

    it('numbers the lines of every product from 1 in the order they are stored', function () {
        foreach ([1 => 4, 2 => 3, 3 => 5, 4 => 6, 5 => 6, 6 => 4, 7 => 2] as $number => $count) {
            $response = actingAs($this->user)->get(route('products.show', pid($number)));
            $stored = ProductComponent::query()->where('product_id', pid($number))->orderBy('position')->pluck('id')->all();

            expect(pageProp($response, 'product.components.*.position'))->toBe(range(1, $count))
                ->and(pageProp($response, 'product.components.*.id'))->toBe($stored);
        }
    });

    it('sends the sales of the last 30 days and the newest 5 sales of the product', function () {
        demoProduct('PRD-JKT-001')->forceFill(['photo_path' => 'products/jacket.jpg'])->save();

        $response = actingAs($this->user)->get(route('products.show', pid(1)));

        expect(pageProp($response, 'recent_sales.*.id'))->toBe(array_map(demoSaleId(...), [57, 52, 43, 40, 38]))
            ->and(pageProp($response, 'recent_sales.*.sold_on'))->toBe(['2026-10-05', '2026-10-01', '2026-09-26', '2026-09-25', '2026-09-24']);

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sales_last_30_days', ['units_sold' => 38, 'revenue' => '3382.0000'])
            ->has('recent_sales.0', fn (Assert $sale) => $sale
                ->where('id', demoSaleId(57))
                ->where('quantity', 4)
                ->where('unit_price', '89.0000')
                ->where('total', '356.0000')
                ->where('sold_on', '2026-10-05')
                ->where('note', 'Web shop')
                ->where('product', [
                    'id' => pid(1),
                    'sku' => 'PRD-JKT-001',
                    'name' => 'Denim Jacket',
                    'photo_url' => Storage::disk('public')->url('products/jacket.jpg'),
                ])));
    });

    it('sends no sales and no margin for the unpriced draft', function () {
        actingAs($this->user)->get(route('products.show', pid(6)))
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.selling_price', null)
                ->where('product.margin', null)
                ->where('product.photo_url', null)
                ->where('sales_last_30_days', ['units_sold' => 0, 'revenue' => '0.0000'])
                ->where('recent_sales', []));
    });

    it('has no capacity and no bottleneck for a product without a composition', function () {
        $product = Product::factory()->create(['labor_cost' => '12.0000', 'overhead_cost' => '3.5000', 'selling_price' => '89.0000']);

        actingAs($this->user)->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.components', [])
                ->where('product.capacity', ['max_producible' => null, 'bottleneck' => null])
                ->where('product.cost', [
                    'material_cost' => '0.0000',
                    'labor_cost' => '12.0000',
                    'overhead_cost' => '3.5000',
                    'unit_cost' => '15.5000',
                ])
                ->where('product.margin', ['amount' => '73.5000', 'percent' => '82.58']));
    });

    it('costs a line whose material was soft-deleted like any other (a delete that bypassed the guard)', function () {
        // The application refuses to delete a material that a product uses; the lines stay costed if one was removed anyway.
        demoMaterial('MAT-BTN-017')->delete();

        actingAs($this->user)->get(route('products.show', pid(1)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('product.components', 4)
                ->where('product.capacity.max_producible', 112)
                ->where('product.capacity.bottleneck.sku', 'MAT-BTN-017')
                ->where('product.cost.unit_cost', '35.7540'));
    });

    it('sends the lines in position order after the composition was reordered', function () {
        $jacket = pid(1);
        // Put the label first and the denim last.
        ProductComponent::query()->where('product_id', $jacket)->where('material_id', demoMaterial('MAT-LBL-WOV')->id)->update(['position' => 0]);
        ProductComponent::query()->where('product_id', $jacket)->where('material_id', demoMaterial('MAT-DEN-012')->id)->update(['position' => 9]);

        $response = actingAs($this->user)->get(route('products.show', $jacket));

        expect(pageProp($response, 'product.components.*.material.sku'))->toBe(['MAT-LBL-WOV', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-DEN-012'])
            ->and(pageProp($response, 'product.components.*.position'))->toBe([1, 2, 3, 4])
            // The figures follow the lines they belong to.
            ->and(pageProp($response, 'product.components.*.units_possible'))->toBe([400, 112, 158, 126])
            ->and(pageProp($response, 'product.capacity.bottleneck.sku'))->toBe('MAT-BTN-017');
    });

    it('follows the stock of the materials immediately', function () {
        // The Denim Jacket needs 8 buttons: 7 are not enough for one unit.
        demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);

        actingAs($this->user)->get(route('products.show', pid(1)))
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.capacity.max_producible', 0)
                ->where('product.capacity.bottleneck.sku', 'MAT-BTN-017'));
    });

    it('answers 404 for an unknown product', function (string $id) {
        actingAs($this->user)->get("/products/{$id}")->assertNotFound();
        actingAs($this->user)->get("/products/{$id}/edit")->assertNotFound();
    })->with(['zero' => '0', 'far away' => '999999999999', 'letters' => 'abc']);

    it('answers 404 for the id just past the last product', function () {
        $next = Product::query()->max('id') + 1;

        actingAs($this->user)->get("/products/{$next}")->assertNotFound();
        actingAs($this->user)->get("/products/{$next}/edit")->assertNotFound();
    });

    it('answers 404 for a soft-deleted product', function () {
        $product = demoProduct('PRD-TSH-002');
        $product->delete();

        actingAs($this->user)->get(route('products.show', $product->id))->assertNotFound();
        actingAs($this->user)->get(route('products.edit', $product->id))->assertNotFound();
    });
});

describe('create and edit', function () {
    it('sends the ProductCreateProps contract: the statuses, and no materials (the picker searches them, S-25)', function () {
        actingAs($this->user)->get(route('products.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/create')
                ->missing('product')
                ->missing('categories')
                ->missing('materials')
                ->has('statuses', 3));
    });

    it('sends a create page that does not grow with the catalogue (at most 5 KB)', function () {
        $size = fn (): int => strlen(json_encode(actingAs($this->user)->get(route('products.create'))->assertOk()->inertiaPage(), JSON_THROW_ON_ERROR));

        $small = $size();
        Material::factory()->count(200)->create();
        $large = $size();

        expect($large)->toBe($small)
            ->and($large)->toBeLessThanOrEqual(5 * 1024);
    });

    it('sends the statuses and the product, but no materials, on the edit page', function () {
        actingAs($this->user)->get(route('products.edit', pid(4)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/edit')
                ->missing('categories')
                ->missing('materials')
                ->has('statuses', 3)
                ->where('product.id', pid(4))
                ->where('product.name', 'Leather Tote')
                ->where('product.cost.unit_cost', '101.0300')
                ->has('product.components', 6));
    });
});

it('reads the materials table once per page and answers within a bounded number of queries', function () {
    $count = function (string $url): array {
        DB::enableQueryLog();
        DB::flushQueryLog();
        actingAs($this->user)->get($url)->assertOk();

        $log = array_column(DB::getQueryLog(), 'query');

        return [
            'all' => count($log),
            'materials' => count(array_filter($log, fn (string $sql): bool => str_contains($sql, 'from "materials"'))),
        ];
    };

    $urls = [
        'index' => productsIndexUrl(),
        'show' => route('products.show', pid(1)),
        'create' => route('products.create'),
        'edit' => route('products.edit', pid(1)),
    ];

    // What each page runs besides the reference lists of a full load: the list is the count, the products, their categories, their
    // lines, the materials of the lines and the units of those (6; the units sold are a sum inside the products query); a product is the row,
    // its category, its lines, their materials and the units of those, and its sales figures and recent sales (7); the create form reads nothing (the picker searches the materials through `materials.options`, S-25)
    // and the edit page costs the lines like the show page (5).
    $budget = ['index' => 6, 'show' => 7, 'create' => 0, 'edit' => 5];
    $materialReads = ['index' => 1, 'show' => 1, 'create' => 0, 'edit' => 1];

    $baseline = array_map($count, $urls);

    Material::factory()->count(40)->create();
    Product::factory()->count(30)->withComponents(4)->create();

    foreach ($urls as $page => $url) {
        // One read of the materials of the lines (none on the create page), never one per row.
        expect($baseline[$page]['materials'])->toBe($materialReads[$page], "the {$page} page reads the materials table {$materialReads[$page]} time(s)")
            ->and($baseline[$page]['all'])->toBeLessThanOrEqual($budget[$page] + REFERENCE_QUERIES)
            // 40 more materials and 30 more products: not one more query.
            ->and($count($url))->toBe($baseline[$page]);
    }
});
