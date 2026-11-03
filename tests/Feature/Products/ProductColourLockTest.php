<?php

use App\Actions\Products\UpdateProduct;
use App\Models\Colour;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Services\Products\FinishedStock;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A colour with stock stays on the product (INV-2-b, AC-1)
|--------------------------------------------------------------------------
|
| The Denim Jacket has White (from the seeder), Black, Indigo and Light wash. A colour with finished units can't be taken off on Edit
| product: one sentence per such colour on `colours.removed.{colourId}`, in list order, and nothing is saved. A colour at 0 comes off as
| before, and added back later it starts at 0.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->jacket->colours()->attach(
        Colour::query()->whereIn('code', ['BLK', 'IND', 'LWH'])->pluck('id')->mapWithKeys(fn (int $id): array => [$id => ['workshop_id' => $this->jacket->workshop_id]])->all(),
    );
});

/** The Edit product save of the jacket, from its edit page, keeping the colours with these names; the composition differs from the seeded one. */
function saveJacketColours(object $test, array $names, array $overrides = [])
{
    return actingAs($test->user)
        ->from(route('products.edit', $test->jacket))
        ->put(route('products.update', $test->jacket), validProductPayload([
            'sku' => $test->jacket->sku, 'name' => 'Denim Jacket Slim', 'status' => 'active',
            'colours' => Colour::query()->whereIn('name', $names)->pluck('id')->all(), ...$overrides,
        ]));
}

/** What a refused save must leave alone: name, composition and colours. */
function jacketState(Product $jacket): array
{
    return [
        $jacket->fresh()->name,
        ProductComponent::query()->where('product_id', $jacket->id)->orderBy('position')->pluck('quantity', 'material_id')->all(),
        $jacket->colours()->pluck('colours.code')->all(),
    ];
}

const INDIGO_LOCKED = 'Indigo has 6 units in stock, so it can\'t be removed from Denim Jacket. Set its stock to 0 first.';

it('refuses to take a colour with units off the product, one sentence per colour in list order, and saves nothing (AC-1)', function (array $stock, array $kept, array $expected) {
    setFinishedStock($this->jacket, $stock);
    $before = jacketState($this->jacket);
    $expected = collect($expected)->mapWithKeys(fn (string $message, string $name): array => ['colours.removed.'.Colour::query()->where('name', $name)->value('id') => $message])->all();

    $response = saveJacketColours($this, $kept);

    if ($expected === []) {
        $response->assertSessionHasNoErrors();
        expect(jacketState($this->jacket))->not->toBe($before)
            ->and($this->jacket->fresh()->name)->toBe('Denim Jacket Slim')
            ->and($this->jacket->colours()->pluck('colours.name')->all())->toBe($kept);

        return;
    }

    $response->assertRedirect(route('products.edit', $this->jacket))->assertSessionHasErrors($expected);

    // One key per colour, in list order (Black before Indigo), each with its own sentence.
    expect(session('errors')->keys())->toBe(array_keys($expected))
        ->and(array_map(fn (string $key): string => session('errors')->first($key), array_keys($expected)))->toBe(array_values($expected))
        ->and(jacketState($this->jacket))->toBe($before);
})->with([
    'Indigo with 6 units taken off' => [['Indigo' => [0, 6, 0, 0]], ['White', 'Black', 'Light wash'], ['Indigo' => INDIGO_LOCKED]],
    'Black and Indigo with units taken off' => [
        ['Indigo' => [0, 6, 0, 0], 'Black' => [1, 0, 2, 1]],
        ['White', 'Light wash'],
        ['Black' => 'Black has 4 units in stock, so it can\'t be removed from Denim Jacket. Set its stock to 0 first.', 'Indigo' => INDIGO_LOCKED],
    ],
    'Indigo with 1 unit taken off' => [
        ['Indigo' => [0, 1, 0, 0]],
        ['White', 'Black', 'Light wash'],
        ['Indigo' => 'Indigo has 1 unit in stock, so it can\'t be removed from Denim Jacket. Set its stock to 0 first.'],
    ],
    'Indigo with 1,000,000 units taken off' => [
        ['Indigo' => [250000, 250000, 250000, 250000]],
        ['White', 'Black', 'Light wash'],
        ['Indigo' => 'Indigo has 1,000,000 units in stock, so it can\'t be removed from Denim Jacket. Set its stock to 0 first.'],
    ],
    'Light wash at 0 taken off, Indigo with units kept' => [['Indigo' => [0, 6, 0, 0], 'Light wash' => [0, 0, 0, 0]], ['White', 'Black', 'Indigo'], []],
]);

it('lets a colour whose stock was set to 0 come off, and starts it at 0 when it is added again (AC-1)', function () {
    setFinishedStock($this->jacket, ['Indigo' => [0, 6, 0, 0]]);
    $all = ['White', 'Black', 'Indigo', 'Light wash'];
    $indigo = demoColour('IND')->id;

    // 6 units: refused. Set to 0 on Update stock, then it comes off.
    saveJacketColours($this, ['White', 'Black', 'Light wash'])->assertSessionHasErrors(["colours.removed.{$indigo}"]);

    $zeroes = $this->jacket->colours()->pluck('colours.id')->mapWithKeys(fn (int $id): array => [$id => sizeIds(['XS' => '0', 'S' => '0', 'M' => '0', 'L' => '0', 'XL' => '0', 'XXL' => '0', 'XXXL' => '0'])])->all();
    actingAs($this->user)->put(route('products.stock.update', $this->jacket), ['stock' => $zeroes])->assertSessionHasNoErrors();
    saveJacketColours($this, ['White', 'Black', 'Light wash'])->assertSessionHasNoErrors();

    expect($this->jacket->colours()->pluck('colours.code')->all())->not->toContain('IND');

    saveJacketColours($this, $all)->assertSessionHasNoErrors();
    actingAs($this->user);
    $stock = app(FinishedStock::class)->of($this->jacket->fresh());

    expect(array_map(fn (array $colour): array => [$colour['colour']->name, $colour['units']], $stock->colours))->toBe([['White', 0], ['Black', 0], ['Indigo', 0], ['Light wash', 0]])
        ->and($stock->units)->toBe(0);
});

it('checks again under the product\'s lock when stock was added after the request, and saves nothing (AC-1)', function () {
    actingAs($this->user);
    $stale = demoProduct('PRD-JKT-001');
    // The request passed with Indigo at 0; before the save, 3 units were counted in Indigo.
    setFinishedStock($this->jacket, ['Indigo' => [0, 0, 3, 0]]);
    $before = jacketState($this->jacket);

    $save = fn () => app(UpdateProduct::class)->handle(
        $stale,
        validProductPayload([
            'sku' => $stale->sku, 'name' => 'Changed',
            'colours' => Colour::query()->whereIn('name', ['White', 'Black', 'Light wash'])->pluck('id')->all(),
        ]),
        $this->user,
    );

    expect($save)->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe([
        'colours.removed.'.demoColour('IND')->id => ['Indigo has 3 units in stock, so it can\'t be removed from Denim Jacket. Set its stock to 0 first.'],
    ]));
    expect(jacketState($this->jacket))->toBe($before);
});

/*
| INV-13-b (AC-6): a colour that open orders still hold, units ordered and not shipped yet, can't come off either.
*/

/** An order of the jacket in Indigo: one row per size label → [quantity, delivered]. */
function orderJacketInIndigo(Product $jacket, array $rows, bool $deleted = false): void
{
    $order = Order::factory()->create();
    $line = OrderLine::factory()->sized(array_map(fn (array $row): int => $row[0], $rows), array_map(fn (array $row): array => [$row[1], 0], $rows))
        ->create(['order_id' => $order->id, 'product_id' => $jacket->id]);
    OrderLineSize::query()->where('order_line_id', $line->id)->update(['colour_id' => demoColour('IND')->id]);

    if ($deleted) {
        $order->delete();
    }
}

it('refuses to take off a colour that open orders still hold, and saves nothing (INV-13 AC-6)', function (array $rows, string $expected) {
    orderJacketInIndigo($this->jacket, $rows);
    $before = jacketState($this->jacket);
    $indigo = demoColour('IND')->id;

    saveJacketColours($this, ['White', 'Black', 'Light wash'])
        ->assertRedirect(route('products.edit', $this->jacket))
        ->assertSessionHasErrors(["colours.removed.{$indigo}" => $expected]);

    expect(jacketState($this->jacket))->toBe($before);
})->with([
    '12 units, none shipped' => [['S' => [4, 0], 'M' => [8, 0]], 'Indigo has 12 units on open orders, so it can\'t be removed from Denim Jacket.'],
    '1 unit left to ship' => [['S' => [4, 3]], 'Indigo has 1 unit on open orders, so it can\'t be removed from Denim Jacket.'],
]);

it('lets a colour come off once its ordered units are all delivered, or when its order was deleted (INV-13 AC-6)', function (bool $deleted, array $rows) {
    orderJacketInIndigo($this->jacket, $rows, $deleted);

    saveJacketColours($this, ['White', 'Black', 'Light wash'])->assertSessionHasNoErrors();

    expect($this->jacket->colours()->pluck('colours.code')->all())->not->toContain('IND');
})->with([
    'all delivered' => [false, ['S' => [4, 4]]],
    'deleted order' => [true, ['S' => [4, 0]]],
]);

it('shows the shelf sentence alone when a colour is both on the shelf and on open orders (INV-13 AC-6)', function () {
    setFinishedStock($this->jacket, ['Indigo' => [0, 6, 0, 0]]);
    orderJacketInIndigo($this->jacket, ['S' => [4, 0]]);

    saveJacketColours($this, ['White', 'Black', 'Light wash'])
        ->assertSessionHasErrors(['colours.removed.'.demoColour('IND')->id => INDIGO_LOCKED]);
});

it('counts only the orders of this product (INV-13 AC-6)', function () {
    orderJacketInIndigo(demoProduct('PRD-JNS-005'), ['M' => [10, 0]]);

    saveJacketColours($this, ['White', 'Black', 'Light wash'])->assertSessionHasNoErrors();
});
