<?php

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The frozen props contract of the product pages (ADR-0006, S-04b)
|--------------------------------------------------------------------------
|
| `resources/js/types/domain.ts` is the contract and did not change when the pages moved from the fixture to the tables. These tests
| pin every product-shaped type: its exact keys IN ORDER (so a Resource that adds, drops or reorders a key is caught) and the type
| and scale of each value (ids and counts are integers, money and quantities are strings of scale 4, the waste and the margin
| percentage are scale 2). Run on the demo data, where every shape occurs (a priced product, the unpriced draft, a bottleneck, ...).
*/

beforeEach(fn () => $this->user = preparePreview($this));

const SCALE_4 = '/^-?\d+\.\d{4}$/';
const SCALE_2 = '/^-?\d+\.\d{2}$/';

/** The keys of a prop, in the order the Resource sends them. */
function keysOf(array $row): array
{
    return array_keys($row);
}

/** @param  array<string, mixed>  $ref  a `MaterialRef` */
function expectMaterialRef(array $ref): void
{
    expect(keysOf($ref))->toBe(['id', 'sku', 'name', 'unit'])
        ->and($ref['id'])->toBeInt()
        ->and($ref['sku'])->toBeString()
        ->and($ref['name'])->toBeString()
        ->and($ref['unit'])->toBeString();
}

/** @param  array<string, mixed>  $capacity  a `Capacity` */
function expectCapacity(array $capacity): void
{
    expect(keysOf($capacity))->toBe(['max_producible', 'bottleneck']);

    if ($capacity['max_producible'] === null) {
        expect($capacity['bottleneck'])->toBeNull();

        return;
    }

    expect($capacity['max_producible'])->toBeInt();
    expectMaterialRef($capacity['bottleneck']);
}

/** @param  array<string, mixed>|null  $margin  a `Margin` or null */
function expectMargin(?array $margin): void
{
    if ($margin === null) {
        return;
    }

    expect(keysOf($margin))->toBe(['amount', 'percent'])
        ->and($margin['amount'])->toMatch(SCALE_4)
        ->and($margin['percent'])->toMatch(SCALE_2);
}

it('sends ProductListItem with its exact keys, order and types', function () {
    $rows = actingAs($this->user)->get(route('products.index'))->inertiaProps('products.data');

    expect($rows)->toHaveCount(7);

    foreach ($rows as $row) {
        expect(keysOf($row))->toBe([
            'id', 'sku', 'name', 'category', 'category_label', 'status', 'status_label', 'photo_url',
            'components_count', 'unit_cost', 'selling_price', 'margin', 'capacity', 'units_sold_last_30_days',
        ])
            ->and($row['id'])->toBeInt()
            ->and($row['components_count'])->toBeInt()
            ->and($row['units_sold_last_30_days'])->toBeInt()
            ->and($row['unit_cost'])->toMatch(SCALE_4)
            ->and($row['status'])->toBeIn(['draft', 'active', 'archived']);

        expect($row['selling_price'] === null || preg_match(SCALE_4, $row['selling_price']) === 1)->toBeTrue();
        expect($row['photo_url'] === null || is_string($row['photo_url']))->toBeTrue();
        expectMargin($row['margin']);
        expectCapacity($row['capacity']);
    }

    // The unpriced draft has no margin and no selling price; the others have both.
    $byStatus = collect($rows)->groupBy('status');
    expect($byStatus['draft'][0]['margin'])->toBeNull()
        ->and($byStatus['draft'][0]['selling_price'])->toBeNull()
        ->and($byStatus['active']->pluck('margin')->filter()->count())->toBe(6);
});

it('sends the paginator of the list with the keys of a paginated resource', function () {
    $products = actingAs($this->user)->get(route('products.index'))->inertiaProps('products');

    expect(keysOf($products))->toBe(['data', 'links', 'meta'])
        ->and(keysOf($products['links']))->toBe(['first', 'last', 'prev', 'next'])
        ->and(keysOf($products['meta']))->toBe(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']);
});

it('sends Product with its exact keys, order and types', function () {
    foreach (range(1, 7) as $number) {
        $response = actingAs($this->user)->get(route('products.show', pid($number)));
        $product = $response->inertiaProps('product');

        expect(keysOf($product))->toBe([
            'id', 'sku', 'name', 'category', 'category_label', 'status', 'status_label', 'description', 'photo_url',
            'labor_cost', 'overhead_cost', 'selling_price', 'cost', 'margin', 'capacity', 'components', 'created_at', 'updated_at',
        ])
            ->and($product['id'])->toBeInt()
            ->and($product['labor_cost'])->toMatch(SCALE_4)
            ->and($product['overhead_cost'])->toMatch(SCALE_4)
            ->and(keysOf($product['cost']))->toBe(['material_cost', 'labor_cost', 'overhead_cost', 'unit_cost'])
            ->and($product['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/')
            ->and($product['updated_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');

        foreach ($product['cost'] as $figure) {
            expect($figure)->toMatch(SCALE_4);
        }

        expectMargin($product['margin']);
        expectCapacity($product['capacity']);
        expect($product['components'])->not->toBeEmpty();

        foreach ($product['components'] as $index => $line) {
            expect(keysOf($line))->toBe([
                'id', 'position', 'material', 'quantity', 'waste_percent', 'effective_quantity', 'unit_cost', 'line_cost',
                'stock_on_hand', 'units_possible', 'is_bottleneck', 'notes',
            ])
                ->and($line['id'])->toBeInt()
                ->and($line['position'])->toBe($index + 1)
                ->and(keysOf($line['material']))->toBe(['id', 'sku', 'name', 'unit', 'photo_url', 'stock_status', 'reorder_level'])
                ->and($line['material']['stock_status'])->toBeIn(['in_stock', 'low', 'out'])
                ->and($line['quantity'])->toMatch(SCALE_4)
                ->and($line['waste_percent'])->toMatch(SCALE_2)
                ->and($line['effective_quantity'])->toMatch(SCALE_4)
                ->and($line['unit_cost'])->toMatch(SCALE_4)
                ->and($line['line_cost'])->toMatch(SCALE_4)
                ->and($line['stock_on_hand'])->toMatch(SCALE_4)
                ->and($line['units_possible'])->toBeInt()
                ->and($line['is_bottleneck'])->toBeBool()
                ->and($line['notes'])->toBeString();
        }
    }
});

it('sends the other props of the show page as before', function () {
    $response = actingAs($this->user)->get(route('products.show', pid(1)));

    expect(keysOf($response->inertiaProps('sales_last_30_days')))->toBe(['revenue', 'units_sold'])
        ->and($response->inertiaProps('sales_last_30_days.units_sold'))->toBeInt()
        ->and($response->inertiaProps('sales_last_30_days.revenue'))->toMatch(SCALE_4);

    foreach ($response->inertiaProps('recent_sales') as $sale) {
        expect(keysOf($sale))->toBe(['id', 'product', 'quantity', 'unit_price', 'total', 'sold_on', 'note'])
            ->and(keysOf($sale['product']))->toBe(['id', 'sku', 'name', 'photo_url'])
            ->and($sale['quantity'])->toBeInt()
            ->and($sale['unit_price'])->toMatch(SCALE_4)
            ->and($sale['total'])->toMatch(SCALE_4);
    }
});

it('sends the props of the create and edit pages as before', function () {
    foreach ([route('products.create'), route('products.edit', pid(1))] as $url) {
        $response = actingAs($this->user)->get($url);

        expect($response->inertiaProps('statuses'))->toBe([
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'archived', 'label' => 'Archived'],
        ]);

        foreach ($response->inertiaProps('materials') as $option) {
            expect(keysOf($option))->toBe(['id', 'sku', 'name', 'unit', 'unit_label', 'unit_cost', 'quantity_on_hand', 'stock_status'])
                ->and($option['id'])->toBeInt()
                ->and($option['unit_cost'])->toMatch(SCALE_4)
                ->and($option['quantity_on_hand'])->toMatch(SCALE_4);
        }
    }

    expect(actingAs($this->user)->get(route('products.create'))->inertiaProps())->not->toHaveKey('product')
        ->and(keysOf(actingAs($this->user)->get(route('products.edit', pid(1)))->inertiaProps('product')))->toHaveCount(18);
});

it('sends the product-shaped props of the dashboard, the sales form and the material page as before', function () {
    $dashboard = actingAs($this->user)->get(route('dashboard'));

    foreach ($dashboard->inertiaProps('alerts') as $alert) {
        expect(keysOf($alert))->toBe(['product', 'bottleneck'])
            ->and(keysOf($alert['product']))->toBe(['id', 'sku', 'name', 'photo_url'])
            ->and(keysOf($alert['bottleneck']))->toBe(['id', 'sku', 'name', 'unit', 'stock_status']);
    }

    expect($dashboard->inertiaProps('alerts'))->toHaveCount(1)
        ->and($dashboard->inertiaProps('stats.unproducible_count'))->toBeInt()
        ->and($dashboard->inertiaProps('stats.products_count'))->toBeInt();

    foreach (actingAs($this->user)->get(route('sales.index'))->inertiaProps('products') as $option) {
        expect(keysOf($option))->toBe(['id', 'sku', 'name', 'photo_url', 'status', 'selling_price']);
    }

    $usage = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')))->inertiaProps('usage');

    expect($usage)->toHaveCount(3);

    foreach ($usage as $row) {
        expect(keysOf($row))->toBe(['product', 'effective_quantity', 'units_covered', 'max_producible', 'bottleneck', 'is_bottleneck', 'next_limit'])
            ->and(keysOf($row['product']))->toBe(['id', 'sku', 'name', 'photo_url', 'status', 'status_label'])
            ->and($row['effective_quantity'])->toMatch(SCALE_4)
            ->and($row['units_covered'])->toBeInt()
            ->and($row['max_producible'])->toBeInt();
        expectMaterialRef($row['bottleneck']);
    }

    // The next limit appears only on the bottleneck row, with a material reference and an integer.
    $next = collect($usage)->firstWhere('is_bottleneck', true)['next_limit'];

    expect(keysOf($next))->toBe(['material', 'max_producible']);
    expectMaterialRef($next['material']);
});

it('sends MaterialResource with the same keys, now with a real used_in_count', function () {
    $material = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')))->inertiaProps('material');

    expect(keysOf($material))->toBe(materialContractKeys())
        ->and($material['used_in_count'])->toBe(3);
});
