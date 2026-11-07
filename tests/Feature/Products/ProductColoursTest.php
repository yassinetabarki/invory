<?php

use App\Actions\Products\UpdateProduct;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Products keep their colours (INV-1-e, ADR-0018)
|--------------------------------------------------------------------------
|
| The demo workshop is the user's: its Denim Jacket and the other demo products have White. Another workshop holds the same 20 colours
| under other ids, to prove a product takes only its own workshop's.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->foreignIndigo = CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Colour::query()->where('code', 'IND')->value('id'));
});

/** The codes of a product's colours in the order the product reports them. */
function colourCodes(Product $product): array
{
    return $product->refresh()->colours->pluck('code')->all();
}

it('gives every product recorded before INV-1 its own workshop\'s White, soft-deleted ones too, and nothing else', function () {
    $workshops = [Workshop::factory()->create(), Workshop::factory()->create()];

    foreach ($workshops as $workshop) {
        CurrentWorkshop::run($workshop, function (): void {
            $products = Product::factory()->count(2)->create();
            $products[0]->delete();
        });
    }

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();

    orderLineColoursMigration()->down();
    productSizeStockColoursMigration()->down();
    productColoursMigration()->down();
    productColoursMigration()->up();
    productSizeStockColoursMigration()->up();
    orderLineColoursMigration()->up();
    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();

    // Raw reads on purpose: nobody is signed in, and the products of the demo workshop (7) are in the table too.
    $links = DB::table('product_colours')
        ->join('products', 'products.id', '=', 'product_colours.product_id')
        ->join('colours', 'colours.id', '=', 'product_colours.colour_id')
        ->get(['products.workshop_id as product_workshop', 'colours.workshop_id as colour_workshop', 'product_colours.workshop_id as link_workshop', 'colours.code']);

    expect($links)->toHaveCount(DB::table('products')->count())
        ->and(DB::table('products')->count())->toBe(11)
        ->and($links->pluck('code')->unique()->all())->toBe(['WHT'])
        ->and($links->every(fn (object $link): bool => $link->product_workshop === $link->colour_workshop && $link->colour_workshop === $link->link_workshop))->toBeTrue();
});

it('refuses a product without colours, or with a colour that is not the workshop\'s, on Add and on Edit, and saves nothing', function (Closure $payload, string $field, ?string $message) {
    $stores = $payload(validProductPayload(), $this->foreignIndigo);
    $updates = $payload(validProductPayload(['sku' => $this->jacket->sku, 'name' => 'Renamed']), $this->foreignIndigo);
    $products = Product::query()->count();
    $expected = $message === null ? [$field] : [$field => $message];

    actingAs($this->user)->post(route('products.store'), $stores)->assertSessionHasErrors($expected);
    actingAs($this->user)->put(route('products.update', $this->jacket), $updates)->assertSessionHasErrors($expected);

    expect(Product::query()->count())->toBe($products)
        ->and($this->jacket->refresh()->name)->toBe('Denim Jacket')
        ->and(colourCodes($this->jacket))->toBe(['WHT']);
})->with([
    'no colours key' => [fn (array $payload) => Arr::except($payload, 'colours'), 'colours', 'Choose at least one colour.'],
    'an empty list' => [fn (array $payload) => [...$payload, 'colours' => []], 'colours', 'Choose at least one colour.'],
    'another workshop\'s colour' => [fn (array $payload, int $foreign) => [...$payload, 'colours' => [$foreign]], 'colours.0', null],
    'an id that does not exist' => [fn (array $payload) => [...$payload, 'colours' => [NO_SUCH_ID]], 'colours.0', null],
    'the same colour twice' => [fn (array $payload) => [...$payload, 'colours' => [demoColour('WHT')->id, demoColour('WHT')->id]], 'colours.0', null],
]);

it('replaces the colours on Edit and keeps the ones picked on Add, whatever the status', function () {
    // Picked Light wash, Black, Indigo; White comes off.
    $picked = [demoColour('LWH')->id, demoColour('BLK')->id, demoColour('IND')->id];

    actingAs($this->user)->put(route('products.update', $this->jacket), validProductPayload(['sku' => $this->jacket->sku, 'colours' => $picked]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product updated.']);

    expect(colourCodes($this->jacket))->toBe(['BLK', 'IND', 'LWH'])
        ->and(DB::table('product_colours')->where('product_id', $this->jacket->id)->pluck('workshop_id')->unique()->all())->toBe([$this->jacket->workshop_id]);

    actingAs($this->user)->post(route('products.store'), validProductPayload(['status' => 'archived', 'colours' => [demoColour('BLK')->id, demoColour('WHT')->id]]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product created.']);

    expect(colourCodes(demoProduct('PRD-NEW-100')))->toBe(['WHT', 'BLK']);
});

it('leaves the colours as they were when the save fails after them', function () {
    // The same material on two lines: the database refuses the second one, after the colours were written in the same transaction.
    $line = ['material_id' => demoMaterial('MAT-DEN-012')->id, 'quantity' => '1', 'waste_percent' => '0', 'notes' => null];
    $data = productData(['sku' => $this->jacket->sku, 'colours' => [demoColour('BLK')->id, demoColour('IND')->id], 'components' => [$line, $line]]);

    expect(fn () => app(UpdateProduct::class)->handle($this->jacket, $data, $this->user))->toThrow(QueryException::class)
        ->and(colourCodes($this->jacket))->toBe(['WHT']);
});

it('reports a product\'s colours in list order whatever order they were picked in, and the forms offer the workshop\'s whole list', function () {
    actingAs($this->user); // the colour queries below are scoped to the user's workshop; another workshop holds 20 as well

    app(UpdateProduct::class)->handle($this->jacket, productData([
        'sku' => $this->jacket->sku,
        'colours' => [demoColour('LWH')->id, demoColour('BLK')->id, demoColour('IND')->id],
    ]), $this->user);

    $shape = fn (string $code): array => ['id' => demoColour($code)->id, 'code' => $code, 'name' => Colour::query()->where('code', $code)->value('name'), 'swatch' => Colour::query()->where('code', $code)->value('swatch')];
    $workshop = Colour::query()->ordered()->pluck('code')->all();

    expect(loadedPageProps(actingAs($this->user)->get(route('products.show', $this->jacket)))['product']['colours'])
        ->toBe([$shape('BLK'), $shape('IND'), $shape('LWH')])
        ->and($workshop)->toHaveCount(20);

    actingAs($this->user)->get(route('products.edit', $this->jacket))->assertInertia(fn (Assert $page) => $page
        ->where('product.colours', [$shape('BLK'), $shape('IND'), $shape('LWH')])
        ->where('colours', fn ($colours) => collect($colours)->pluck('code')->all() === $workshop));

    actingAs($this->user)->get(route('products.create'))->assertInertia(fn (Assert $page) => $page
        ->has('colours', 20)
        ->where('colours.0', $shape('WHT'))
        ->where('colours', fn ($colours) => collect($colours)->pluck('code')->all() === $workshop));
});
