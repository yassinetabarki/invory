<?php

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductRestockLine;
use App\Models\Sale;
use App\Models\SizeList;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Saving a restock (INV-3-b, AC-1 and AC-3)
|--------------------------------------------------------------------------
|
| `POST restocks` adds the units typed to the shelf and records the restock, all or nothing. The catalogue is the card's: the Denim
| Jacket in Black, Indigo and Light wash with 10 units, the Denim Jeans 5-pocket in Indigo with none, the Leather Tote ("One size") in
| Camel (4) and Black. Today is the frozen demo day.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans, 'tote' => $this->tote] = restockCatalogue();
});

/** An error key with `{IND}` for a colour code and `{M}` or `{One size}` for a size label, as ids. */
function fieldKey(string $key): string
{
    $colour = null;

    return (string) preg_replace_callback('/\{([^}]+)\}/', function (array $match) use (&$colour): string {
        if ($colour === null) {
            return (string) ($colour = demoColour($match[1])->id);
        }

        return (string) array_key_first(sizeIds([$match[1] => 0], $match[1] === 'One size' ? SizeList::ONE_SIZE : SizeList::S_TO_XL));
    }, $key);
}

/** AC-1's restock: Denim Jacket 24 and Denim Jeans 5-pocket 10. */
function cardRestock(Product $jacket, Product $jeans, array $overrides = []): array
{
    return restockBody([
        restockLine($jacket, ['Indigo' => ['S' => 4, 'M' => 8, 'L' => 6, 'XL' => 2], 'Black' => ['M' => '4']]),
        restockLine($jeans, ['Indigo' => ['M' => 6, 'L' => 4]]),
    ], $overrides);
}

it('adds the units to the shelf and records the restock, changing nothing else (AC-1)', function () {
    $before = [
        'materials' => Material::query()->without('category', 'unit')->orderBy('id')->get()->map->only(['id', 'quantity_on_hand', 'unit_cost', 'updated_at'])->all(),
        'movements' => StockMovement::query()->count(),
        'sales' => Sale::query()->count(),
        'products' => Product::withTrashed()->orderBy('id')->get(['id', 'updated_at', 'status'])->toArray(),
    ];

    actingAs($this->user)
        ->post(route('restocks.store'), cardRestock($this->jacket, $this->jeans, ['reference' => ' BL-0412 ', 'note' => 'Batch sewn by Atelier Nour.']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('restocks.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '34 units restocked.']);

    expect(shelfByColour($this->jacket))->toBe([
        'Black' => ['S' => 1, 'M' => 4, 'L' => 2, 'XL' => 1],
        'Indigo' => ['S' => 6, 'M' => 8, 'L' => 9, 'XL' => 3],
    ])->and(shelfByColour($this->jeans))->toBe(['Indigo' => ['M' => 6, 'L' => 4]]);

    $restock = ProductRestock::query()->sole();
    expect($restock->only(['reference', 'note', 'units', 'user_id', 'undone_at']))->toBe([
        'reference' => 'BL-0412', 'note' => 'Batch sewn by Atelier Nour.', 'units' => 34, 'user_id' => $this->user->id, 'undone_at' => null,
    ])->and($restock->restocked_on->toDateString())->toBe('2026-10-08')
        ->and($restock->workshop_id)->toBe($this->user->workshop_id);

    $lines = ProductRestockLine::query()->with(['colour', 'size'])->orderBy('id')->get()
        ->map(fn (ProductRestockLine $line): string => "{$line->position} {$line->product_id} {$line->colour->name} {$line->size->label} {$line->units}")->all();
    expect($lines)->toEqualCanonicalizing([
        "1 {$this->jacket->id} Black M 4",
        "1 {$this->jacket->id} Indigo S 4", "1 {$this->jacket->id} Indigo M 8", "1 {$this->jacket->id} Indigo L 6", "1 {$this->jacket->id} Indigo XL 2",
        "2 {$this->jeans->id} Indigo M 6", "2 {$this->jeans->id} Indigo L 4",
    ]);

    expect([
        'materials' => Material::query()->without('category', 'unit')->orderBy('id')->get()->map->only(['id', 'quantity_on_hand', 'unit_cost', 'updated_at'])->all(),
        'movements' => StockMovement::query()->count(),
        'sales' => Sale::query()->count(),
        'products' => Product::withTrashed()->orderBy('id')->get(['id', 'updated_at', 'status'])->toArray(),
    ])->toEqual($before);
});

it('says "1 unit restocked." for one unit, and restocks a "One size" product by colour', function () {
    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([restockLine($this->tote, ['Camel' => ['One size' => 1]])]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 unit restocked.']);

    expect(shelfByColour($this->tote))->toBe(['Camel' => ['One size' => 5]]);
});

it('restocks draft and archived products like active ones (AC-1)', function (string $status) {
    $this->jeans->forceFill(['status' => $status])->save();

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([restockLine($this->jeans, ['Indigo' => ['S' => 3]])]))
        ->assertSessionHasNoErrors();

    expect(shelfByColour($this->jeans))->toBe(['Indigo' => ['S' => 3]]);
})->with(['draft', 'archived']);

it('refuses with the card\'s message under its field, and saves nothing (AC-3)', function (Closure $body, string $key, string $message) {
    actingAs($this->user)
        ->post(route('restocks.store'), $body($this->jacket, $this->jeans, $this->tote))
        ->assertSessionHasErrors([fieldKey($key) => $message]);

    expect(ProductRestock::query()->count())->toBe(0)
        ->and(shelfByColour($this->jacket))->toBe(['Black' => ['S' => 1, 'L' => 2, 'XL' => 1], 'Indigo' => ['S' => 2, 'L' => 3, 'XL' => 1]]);
})->with([
    'not a whole number' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => '2.5']])]),
        'products.0.units.{IND}.{M}', 'Indigo M: Enter a whole number from 0 to 1,000,000.',
    ],
    'negative' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => -1]])]),
        'products.0.units.{IND}.{M}', 'Indigo M: Enter a whole number from 0 to 1,000,000.',
    ],
    'over 1,000,000 in a field' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => 1000001]])]),
        'products.0.units.{IND}.{M}', 'Indigo M: Enter a whole number from 0 to 1,000,000.',
    ],
    'a "One size" field names its colour' => [
        fn (Product $jacket, Product $jeans, Product $tote) => restockBody([restockLine($tote, ['Camel' => ['One size' => 'x']])]),
        'products.0.units.{CAM}.{One size}', 'Camel: Enter a whole number from 0 to 1,000,000.',
    ],
    'a line with nothing typed' => [
        fn (Product $jacket, Product $jeans) => restockBody([restockLine($jacket, ['Indigo' => ['M' => 4]]), restockLine($jeans, [])]),
        'products.1', 'Enter at least one unit of Denim Jeans 5-pocket, or remove it from this restock.',
    ],
    'nothing typed at all' => [
        fn () => restockBody([]),
        'products', 'Enter at least one unit to restock.',
    ],
    'a date in the future' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => 4]])], ['restocked_on' => '2026-10-09']),
        'restocked_on', "The date can't be in the future.",
    ],
    'a reference of 65 characters' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => 4]])], ['reference' => str_repeat('R', 65)]),
        'reference', 'Keep the reference to 64 characters or fewer.',
    ],
    'a note of 256 characters' => [
        fn (Product $jacket) => restockBody([restockLine($jacket, ['Indigo' => ['M' => 4]])], ['note' => str_repeat('n', 256)]),
        'note', 'Keep the note to 255 characters or fewer.',
    ],
]);

it('refuses a 51st product (AC-3)', function () {
    $lines = Product::factory()->count(51)->create(['category' => 'apparel'])
        ->map(fn (Product $product): array => restockLine($product, [$product->colours()->value('name') ?? 'White' => ['M' => 1]]))->all();

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody($lines))
        ->assertSessionHasErrors(['products' => 'A restock can hold up to 50 products.']);
});

it('refuses a product deleted, or whose colours or sizes changed, since the page; nothing is saved (AC-3)', function (Closure $change, string $message) {
    $body = cardRestock($this->jacket, $this->jeans);
    $change($this->jacket);

    actingAs($this->user)
        ->post(route('restocks.store'), $body)
        ->assertSessionHasErrors(['products.0' => $message]);

    expect(ProductRestock::query()->count())->toBe(0)
        ->and(shelfByColour($this->jeans))->toBe([]);
})->with([
    'deleted' => [fn (Product $jacket) => $jacket->delete(), 'Denim Jacket was deleted. Remove it from this restock.'],
    'a colour taken off' => [
        fn (Product $jacket) => $jacket->colours()->detach(demoColour('LWH')->id),
        'The sizes or colours of Denim Jacket have changed. Enter its units again.',
    ],
    'a colour added' => [
        fn (Product $jacket) => $jacket->colours()->attach(demoColour('RED')->id, ['workshop_id' => $jacket->workshop_id]),
        'The sizes or colours of Denim Jacket have changed. Enter its units again.',
    ],
]);

it('refuses a variant that would go over 1,000,000 units on the shelf, and accepts exactly 1,000,000 (AC-3)', function () {
    setShelf($this->jacket, ['Indigo' => ['M' => 999990]]);

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([restockLine($this->jacket, ['Indigo' => ['M' => 11]])]))
        ->assertSessionHasErrors([fieldKey('products.0.units.{IND}.{M}') => 'Indigo M: This would put more than 1,000,000 units on the shelf.']);
    expect(shelfByColour($this->jacket)['Indigo']['M'])->toBe(999990);

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([restockLine($this->jacket, ['Indigo' => ['M' => 10]])]))
        ->assertSessionHasNoErrors();
    expect(shelfByColour($this->jacket)['Indigo']['M'])->toBe(1000000);
});

it('refuses another workshop\'s product as if it didn\'t exist', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Product => Product::factory()->create(['category' => 'apparel']));

    actingAs($this->user)
        ->post(route('restocks.store'), restockBody([['product_id' => $theirs->id, 'units' => []]]))
        ->assertSessionHasErrors(['products.0' => 'This product no longer exists. Remove it from this restock.']);

    expect(ProductRestock::query()->count())->toBe(0);
});

it('lets Update stock saved after a restock win, as today (AC-1)', function () {
    actingAs($this->user)->post(route('restocks.store'), cardRestock($this->jacket, $this->jeans))->assertSessionHasNoErrors();

    $colours = $this->jacket->colours()->pluck('colours.id')->all();
    $sizes = array_keys(sizeIds(['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, 'XXL' => 0, 'XXXL' => 0]));
    $units = array_fill_keys($colours, array_fill_keys($sizes, 0));
    $units[demoColour('IND')->id][$sizes[2]] = 2;

    app(UpdateFinishedStock::class)->handle($this->jacket, $units);

    expect(shelfByColour($this->jacket))->toBe(['Indigo' => ['M' => 2]])
        ->and(ProductRestock::query()->sole()->units)->toBe(34);
});

it('sends a guest to the login page', function () {
    $this->post(route('restocks.store'), restockBody([]))->assertRedirect(route('login'));
});
