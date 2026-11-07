<?php

use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The Restocks page, a restock's panel and New restock (INV-3-d, AC-2, AC-3, AC-5)
|--------------------------------------------------------------------------
|
| The card's three restocks are recorded through the real route: 18 Oct "Return Boutique Lina" (Classic White T-shirt 12, undone),
| 22 Oct by Sami (Leather Tote 6), 25 Oct at 14:32 "BL-0412" (Denim Jacket 24 · Denim Jeans 5-pocket 10).
*/

beforeEach(function () {
    $this->admin = prepareDemo($this);
    $this->admin->forceFill(['name' => 'Admin'])->save();
    $this->sami = User::factory()->create(['name' => 'Sami', 'workshop_id' => $this->admin->workshop_id]);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans, 'tote' => $this->tote] = restockCatalogue();
    $this->tshirt = demoProduct('PRD-TSH-002');

    $record = function (User $user, string $at, array $lines, array $details = []): ProductRestock {
        $this->travelTo(CarbonImmutable::parse($at));
        actingAs($user)->post(route('restocks.store'), restockBody($lines, $details))->assertSessionHasNoErrors();

        return ProductRestock::query()->latest('id')->firstOrFail();
    };

    $this->returned = $record($this->admin, '2026-10-18 09:00', [restockLine($this->tshirt, [$this->tshirt->colours()->value('name') => ['M' => 12]])], ['reference' => 'Return Boutique Lina']);
    actingAs($this->admin)->post(route('restocks.undo', $this->returned))->assertSessionHasNoErrors();
    $this->tote6 = $record($this->sami, '2026-10-22 10:00', [restockLine($this->tote, ['Camel' => ['One size' => 6]])]);
    $this->bl0412 = $record($this->admin, '2026-10-25 14:32', [
        restockLine($this->jacket, ['Indigo' => ['S' => 4, 'M' => 8, 'L' => 6, 'XL' => 2], 'Black' => ['M' => 4]]),
        restockLine($this->jeans, ['Indigo' => ['M' => 6, 'L' => 4]]),
    ], ['reference' => 'BL-0412', 'note' => 'Batch sewn by Atelier Nour, delivered with the invoice.']);
});

it('lists the restocks newest first, each with its products, units and who recorded it (AC-2)', function () {
    $response = actingAs($this->admin)->get(route('restocks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('restocks/index')
            ->where('filters', ['search' => null, 'product' => null, 'restock' => null])
            ->where('product_filter', null)
            ->where('has_restocks', true)
            ->where('restock', null)
            ->where('restocks.meta.total', 3));

    expect(pageProp($response, 'restocks.data'))->toBe([
        ['id' => $this->bl0412->id, 'restocked_on' => '2026-10-25', 'reference' => 'BL-0412', 'units' => 34,
            'products' => [['name' => 'Denim Jacket', 'units' => 24], ['name' => 'Denim Jeans 5-pocket', 'units' => 10]],
            'more_products' => 0, 'recorded_by' => 'Admin', 'undone' => false],
        ['id' => $this->tote6->id, 'restocked_on' => '2026-10-22', 'reference' => null, 'units' => 6,
            'products' => [['name' => 'Leather Tote', 'units' => 6]], 'more_products' => 0, 'recorded_by' => 'Sami', 'undone' => false],
        ['id' => $this->returned->id, 'restocked_on' => '2026-10-18', 'reference' => 'Return Boutique Lina', 'units' => 12,
            'products' => [['name' => 'Classic White T-shirt', 'units' => 12]], 'more_products' => 0, 'recorded_by' => 'Admin', 'undone' => true],
    ]);
});

it('names two products and counts the others: "and 2 more" (AC-2)', function () {
    actingAs($this->admin)->post(route('restocks.store'), restockBody([
        restockLine($this->jacket, ['Black' => ['S' => 24]]),
        restockLine($this->tote, ['Black' => ['One size' => 6]]),
        restockLine($this->jeans, ['Indigo' => ['S' => 1]]),
        restockLine($this->tshirt, [$this->tshirt->colours()->value('name') => ['S' => 1]]),
    ]))->assertSessionHasNoErrors();

    $response = actingAs($this->admin)->get(route('restocks.index'));

    expect(pageProp($response, 'restocks.data.0.products'))->toBe([['name' => 'Denim Jacket', 'units' => 24], ['name' => 'Leather Tote', 'units' => 6]])
        ->and(pageProp($response, 'restocks.data.0.more_products'))->toBe(2);
});

it('filters by reference, note or product name, and by product (AC-2)', function (array $query, array $expected) {
    $response = actingAs($this->admin)->get(route('restocks.index', $query))->assertOk();

    $names = ['bl0412' => $this->bl0412->id, 'tote6' => $this->tote6->id, 'returned' => $this->returned->id];
    expect(pageProp($response, 'restocks.data.*.id'))->toBe(array_map(fn (string $name): int => $names[$name], $expected));
})->with([
    'reference' => [['search' => 'BL-04'], ['bl0412']],
    'note' => [['search' => 'atelier nour'], ['bl0412']],
    'product name' => [['search' => 'tote'], ['tote6']],
    'no match' => [['search' => 'BL-9'], []],
]);

it('filters by product and names it for the Product filter, even once deleted (AC-2)', function () {
    $this->tote->delete();

    $response = actingAs($this->admin)->get(route('restocks.index', ['product' => $this->tote->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.product', $this->tote->id)
            ->where('product_filter', ['id' => $this->tote->id, 'name' => 'Leather Tote', 'sku' => 'PRD-TOT-004', 'status' => 'active', 'deleted' => true]));

    expect(pageProp($response, 'restocks.data.*.id'))->toBe([$this->tote6->id]);
});

it('tells "No restocks yet" from no match', function () {
    $other = CurrentWorkshop::run(Workshop::factory()->create(), fn (): User => User::factory()->create());

    actingAs($other)->get(route('restocks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('has_restocks', false)->where('restocks.meta.total', 0));
});

it('opens a restock\'s panel from the address, with only the colours that got units (AC-5)', function () {
    $response = actingAs($this->admin)->get(route('restocks.index', ['restock' => $this->bl0412->id]))->assertOk();
    $detail = pageProp($response, 'restock');
    $apparel = array_map(fn (string $label): array => ['id' => array_key_first(sizeIds([$label => 0])), 'label' => $label], ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']);

    expect(collect($detail)->only(['id', 'reference', 'units', 'note', 'recorded_at', 'recorded_by', 'products_count', 'undone', 'undone_at', 'undone_by', 'undo_refusal'])->all())->toBe([
        'id' => $this->bl0412->id, 'reference' => 'BL-0412', 'units' => 34, 'recorded_by' => 'Admin', 'undone' => false,
        'note' => 'Batch sewn by Atelier Nour, delivered with the invoice.', 'recorded_at' => '2026-10-25T14:32', 'products_count' => 2,
        'undone_at' => null, 'undone_by' => null, 'undo_refusal' => null,
    ]);

    [$jacket, $jeans] = $detail['blocks'];
    expect($jacket['product'])->toBe(['id' => $this->jacket->id, 'name' => 'Denim Jacket', 'sku' => 'PRD-JKT-001', 'status' => 'active', 'deleted' => false])
        ->and($jacket['sizes'])->toBe($apparel)
        ->and($jacket['one_size'])->toBeFalse()
        ->and($jacket['units'])->toBe(24)
        ->and(array_map(fn (array $row): array => [$row['colour']['name'], $row['units'], $row['total']], $jacket['rows']))->toBe([
            ['Black', [0, 0, 4, 0, 0, 0, 0], 4],
            ['Indigo', [0, 4, 8, 6, 2, 0, 0], 20],
        ])
        ->and($jacket['totals'])->toBe([0, 4, 12, 6, 2, 0, 0])
        ->and($jeans['units'])->toBe(10)
        ->and($jeans['totals'])->toBeNull();
});

it('says why a restock can\'t be undone, and who undid one (AC-5, AC-6)', function () {
    setShelf($this->jacket, ['Indigo' => ['M' => 2]]);

    expect(pageProp(actingAs($this->admin)->get(route('restocks.index', ['restock' => $this->bl0412->id])), 'restock.undo_refusal'))
        ->toBe("This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead.");

    $returned = pageProp(actingAs($this->admin)->get(route('restocks.index', ['restock' => $this->returned->id])), 'restock');
    expect($returned['undone'])->toBeTrue()
        ->and($returned['undone_by'])->toBe('Admin')
        ->and($returned['undone_at'])->toBe('2026-10-18T09:00')
        ->and($returned['undo_refusal'])->toBeNull();
});

it('shows "—" for who recorded a restock once that account is deleted', function () {
    $this->sami->delete();

    expect(pageProp(actingAs($this->admin)->get(route('restocks.index')), 'restocks.data.1.recorded_by'))->toBeNull();
});

it('marks a deleted product in the panel (AC-5)', function () {
    $this->jeans->delete();

    expect(pageProp(actingAs($this->admin)->get(route('restocks.index', ['restock' => $this->bl0412->id])), 'restock.blocks.1.product.deleted'))->toBeTrue();
});

it('opens no panel for another workshop\'s restock or an unknown one', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): ProductRestock => ProductRestock::factory()->create());

    actingAs($this->admin)->get(route('restocks.index', ['restock' => $theirs->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('restock', null));
    actingAs($this->admin)->get(route('restocks.index', ['restock' => 999999]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('restock', null));
});

it('keeps the list\'s queries flat however many restocks a page shows', function () {
    $five = countQueries(fn () => actingAs($this->admin)->get(route('restocks.index'))->assertOk());

    foreach (range(1, 22) as $i) {
        actingAs($this->admin)->post(route('restocks.store'), restockBody([
            restockLine($this->jacket, ['Black' => ['S' => 1]]), restockLine($this->jeans, ['Indigo' => ['S' => 1]]),
        ]))->assertSessionHasNoErrors();
    }

    expect(countQueries(fn () => actingAs($this->admin)->get(route('restocks.index'))->assertOk()))->toBe($five);
});

it('opens New restock empty, or with a product\'s line from its page (AC-3, AC-7)', function () {
    actingAs($this->admin)->get(route('restocks.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('restocks/create')->where('preset', null));

    $preset = pageProp(actingAs($this->admin)->get(route('restocks.create', ['product' => $this->tote->id])), 'preset');

    expect($preset['product']['name'])->toBe('Leather Tote')
        ->and($preset['one_size'])->toBeTrue()
        ->and($preset['sizes'])->toBe([['id' => array_key_first(sizeIds(['One size' => 0], 'one_size')), 'label' => 'One size']])
        ->and(array_map(fn (array $row): array => [$row['colour']['name'], $row['units']], $preset['stock']['colours']))->toBe([['Camel', 10], ['Black', 0]]);
});

it('offers every product but deleted ones in the picker, draft and archived with their status (AC-3)', function () {
    $this->jeans->forceFill(['status' => 'draft'])->save();
    $this->tshirt->forceFill(['status' => 'archived'])->save();
    $this->tote->delete();

    $options = actingAs($this->admin)->getJson(route('restocks.products.options', ['search' => 'e']))->assertOk()->json();
    $byName = collect($options)->keyBy('name');

    expect($byName->get('Denim Jeans 5-pocket')['status'])->toBe('draft')
        ->and($byName->get('Classic White T-shirt')['status'])->toBe('archived')
        ->and($byName->has('Leather Tote'))->toBeFalse()
        ->and(collect($options)->pluck('name')->all())->toBe(collect($options)->pluck('name')->sortBy(fn (string $name): string => mb_strtolower($name))->values()->all());

    $included = actingAs($this->admin)->getJson(route('restocks.products.options', ['search' => 'zzz', 'include' => [$this->jacket->id]]))->json();
    expect(collect($included)->pluck('name')->all())->toBe(['Denim Jacket']);
});

it('serves a product\'s line, and 404 for a deleted or another workshop\'s product (AC-3)', function () {
    $line = actingAs($this->admin)->getJson(route('restocks.products.line', $this->jacket))->assertOk()->json();

    expect($line['product']['sku'])->toBe('PRD-JKT-001')
        ->and(collect($line['sizes'])->pluck('label')->all())->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
        ->and($line['stock']['units'])->toBe(34);

    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Product => Product::factory()->create(['category' => 'apparel']));
    actingAs($this->admin)->getJson(route('restocks.products.line', $theirs->id))->assertNotFound();

    $this->jeans->delete();
    actingAs($this->admin)->getJson(route('restocks.products.line', $this->jeans->id))->assertNotFound();
});

it('sends a guest to the login page', function () {
    auth()->forgetGuards();

    $this->get(route('restocks.index'))->assertRedirect(route('login'));
    $this->get(route('restocks.create'))->assertRedirect(route('login'));
});
