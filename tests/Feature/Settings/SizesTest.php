<?php

use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings > Sizes on the server (INV-18-d, ADR-0028)
|--------------------------------------------------------------------------
|
| Every factory workshop holds the 19 default sizes at 10, 20 … 190: XS first, XXL sixth (60), One size last. "Used by" counts the
| non-deleted products that have the size. A rename shows in the variant SKUs the demo Denim Jacket (PRD-JKT-001) is read with.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** The labels of the signed-in workshop's sizes, in list order, as the Settings page lists them. */
function listedSizes(User $user): array
{
    return array_column(actingAs($user)->get(route('settings.sizes.index'))->inertiaProps()['sizes'], 'label');
}

/** The variants of the Denim Jacket in Indigo, `sku => barcode number`, as the product page's barcodes read them. */
function indigoJacketBarcodes(User $user): array
{
    $barcodes = loadedPageProp(actingAs($user)->get(route('products.show', demoProduct('PRD-JKT-001'))), 'barcodes');
    $indigo = collect($barcodes['colours'])->first(fn (array $colour): bool => $colour['colour']['name'] === 'Indigo');

    return array_column($indigo['variants'], 'barcode', 'sku');
}

it('adds sizes in their place, and renames one everywhere it is read without touching a barcode', function () {
    $this->user = prepareDemo($this);
    $jacket = demoProduct('PRD-JKT-001');
    $indigo = demoColour('IND');
    $jacket->colours()->syncWithPivotValues([$indigo->id], ['workshop_id' => $jacket->workshop_id]);

    $barcode = Barcode::factory()->create(['number' => '2000000000015', 'colour_id' => $indigo->id, 'size_id' => sizeId('XXL')]);
    BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => 'denim jacket']);

    $store = fn (array $payload) => actingAs($this->user)->post(route('settings.sizes.store'), $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Size created.']);

    // XXS at 15 sits between XS (10) and S (20); a second size at 15 keeps its creation order, whatever its name.
    $store(['label' => 'XXS', 'position' => '15']);
    $store(['label' => 'AAA', 'position' => '15']);
    // No position: after the last one (One size, 190). A new size is never One size and is on no product.
    $store(['label' => 'Kids', 'position' => '']);

    $sizes = Size::query()->ordered()->get();

    expect($sizes->pluck('label')->take(5)->all())->toBe(['XS', 'XXS', 'AAA', 'S', 'M'])
        ->and($sizes->last()->only(['label', 'position', 'is_one_size']))->toBe(['label' => 'Kids', 'position' => 191, 'is_one_size' => false])
        ->and($sizes->where('is_one_size', true)->pluck('label')->all())->toBe(['One size'])
        ->and(DB::table('product_sizes')->whereIn('size_id', $sizes->whereIn('label', ['XXS', 'AAA', 'Kids'])->pluck('id'))->count())->toBe(0);

    expect(indigoJacketBarcodes($this->user))->toHaveKey('PRD-JKT-001-IND-XXL', '2000000000015');

    // Renaming XXL changes the SKU on read, never the barcode; an empty position lists it last (after Kids, 191), as on Add;
    // XL in other capitals is its own name.
    actingAs($this->user)->put(route('settings.sizes.update', sizeId('XXL')), ['label' => '2XL', 'position' => ''])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Size updated.']);
    actingAs($this->user)->put(route('settings.sizes.update', sizeId('XL')), ['label' => 'Xl', 'position' => '50'])->assertSessionHasNoErrors();

    $variants = indigoJacketBarcodes($this->user);

    expect($variants)->toHaveKey('PRD-JKT-001-IND-2XL', '2000000000015')
        ->and($variants)->not->toHaveKey('PRD-JKT-001-IND-XXL')
        ->and(Size::query()->whereKey($barcode->size_id)->first()->only(['label', 'position']))->toBe(['label' => '2XL', 'position' => 192])
        ->and(array_slice(listedSizes($this->user), 0, 8))->toBe(['XS', 'XXS', 'AAA', 'S', 'M', 'L', 'Xl', 'XXXL'])
        ->and(last(listedSizes($this->user)))->toBe('2XL');
});

it('refuses a wrong entry with the one message of its field, on Add and on Edit, and saves nothing', function (array $payload, string $field, string $message) {
    $xxl = sizeId('XXL');
    $before = Size::query()->count();

    foreach ([
        fn () => actingAs($this->user)->post(route('settings.sizes.store'), ['label' => 'New', 'position' => '', ...$payload]),
        fn () => actingAs($this->user)->put(route('settings.sizes.update', $xxl), ['label' => 'New', 'position' => '', ...$payload]),
    ] as $send) {
        $send()->assertRedirect()->assertSessionHasErrors([$field => $message]);

        expect(session('errors')->keys())->toBe([$field])
            ->and(session('errors')->get($field))->toBe([$message]);
    }

    expect(Size::query()->count())->toBe($before)
        ->and(Size::query()->find($xxl)->label)->toBe('XXL');
})->with([
    'an empty name' => [['label' => ''], 'label', "Enter the size's name."],
    'a name of spaces' => [['label' => '   '], 'label', "Enter the size's name."],
    'a 13-character name' => [['label' => str_repeat('a', 13)], 'label', 'Keep the name to 12 characters or fewer.'],
    '"xl" while XL exists' => [['label' => 'xl'], 'label', 'You already have a size named "XL".'],
    'a position of -1' => [['position' => '-1'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
    'a position of 32768' => [['position' => '32768'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
    'a position of "abc"' => [['position' => 'abc'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
]);

it('accepts the name of a size of another workshop, and a 12-character name', function () {
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Size::factory()->create(['label' => 'Petite']));

    actingAs($this->user)->post(route('settings.sizes.store'), ['label' => 'Petite', 'position' => '0'])->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('settings.sizes.store'), ['label' => str_repeat('b', 12), 'position' => ''])->assertSessionHasNoErrors();

    // Position 0 lists it first.
    expect(listedSizes($this->user)[0])->toBe('Petite')
        ->and(Size::query()->where('label', 'Petite')->count())->toBe(1);
});

it('sends a guest to the login page', function (string $method, string $route) {
    $this->{$method}(route($route, [1]))->assertRedirect(route('login'));
})->with([
    'list' => ['get', 'settings.sizes.index'],
    'add' => ['post', 'settings.sizes.store'],
    'edit' => ['put', 'settings.sizes.update'],
]);

it("answers Not Found for another workshop's size", function () {
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn () => Size::query()->where('label', 'XL')->firstOrFail());

    actingAs($this->user)->put(route('settings.sizes.update', $foreign->id), ['label' => 'Mine now', 'position' => ''])->assertNotFound();

    expect($foreign->refresh()->label)->toBe('XL');
});

it('lists every size of the workshop in one list, with the agreed shape and the products that use each', function () {
    Product::factory()->withSizes(['S', 'M'])->create();
    Product::factory()->draft()->withSizes(['S'])->create();
    Product::factory()->archived()->withSizes(['One size'])->create();
    Product::factory()->withSizes(['One size'])->create()->delete();

    $page = actingAs($this->user)->get(route('settings.sizes.index'))->assertOk();

    $page->assertInertia(fn (Assert $page) => $page
        ->component('settings/sizes')
        ->has('sizes', 19)
        ->has('sizes.0', fn (Assert $row) => $row
            ->where('id', fn ($id) => is_int($id))
            ->where('label', 'XS')
            ->where('one_size', false)
            ->where('position', 10)
            ->where('usage_count', 0)
            ->etc())
        ->missing('sizes.data')
        ->where('sizes', fn ($sizes) => collect($sizes)->pluck('usage_count', 'label')->only(['S', 'M', 'XL', 'One size'])->all() == ['S' => 2, 'M' => 1, 'XL' => 0, 'One size' => 1]
            && collect($sizes)->last()['one_size'] === true
            && collect($sizes)->where('one_size', true)->count() === 1));
});
