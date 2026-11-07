<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Arr;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Product categories suggest sizes (INV-18-i, AC-4, ADR-0028)
|--------------------------------------------------------------------------
|
| Categories are shared by every workshop, the suggestion is not: each workshop keeps its own rows in `product_category_sizes`, and no
| row means its One size. A suggestion decides what Add product pre-selects and nothing else.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workshopId = $this->user->workshop_id;
});

/** The ids of the workshop's sizes with these labels, in list order. */
function sizeIdsIn(int $workshopId, array $labels): array
{
    return CurrentWorkshop::run($workshopId, fn (): array => Size::query()->whereIn('label', $labels)->ordered()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all());
}

/** The labels a category suggests in the workshop, as `product_category_sizes` holds them (no row: empty). */
function suggestedLabels(int $workshopId, string $code): array
{
    return CurrentWorkshop::run($workshopId, fn (): array => ProductCategory::query()->where('code', $code)->firstOrFail()->suggestedSizes()->pluck('label')->all());
}

/** One row of Settings › Product categories. */
function categoryRow(User $user, string $code): array
{
    return Arr::first(
        actingAs($user)->get(route('settings.product-categories.index'))->inertiaProps('categories'),
        fn (array $row): bool => $row['code'] === $code,
    );
}

it("saves a category's suggested sizes for this workshop only and Add product pre-selects them", function () {
    $footwear = ProductCategory::query()->where('code', 'footwear')->firstOrFail();
    $other = Workshop::factory()->create();
    $products = Product::factory()->count(2)->create(['category' => 'footwear']);
    $productSizes = fn (): array => $products->map(fn (Product $product): array => sizesOf($product)->pluck('label')->all())->all();
    $before = $productSizes();
    $forty = range(38, 44);
    $mine = sizeIdsIn($this->workshopId, array_map('strval', $forty));
    $addProduct = fn (): array => actingAs($this->user)->get(route('products.create'))->inertiaProps('category_sizes.footwear');

    // Sent in any order, stored and read back in list order.
    actingAs($this->user)
        ->put(route('settings.product-categories.update', $footwear), ['name' => 'Footwear', 'suggested_sizes' => array_reverse($mine)])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category updated.']);

    expect(suggestedLabels($this->workshopId, 'footwear'))->toBe(array_map('strval', $forty))
        ->and(suggestedLabels($other->id, 'footwear'))->toHaveCount(11)
        ->and($addProduct())->toBe($mine)
        ->and($productSizes())->toBe($before);

    // A rename that sends no suggestion leaves it alone.
    actingAs($this->user)->put(route('settings.product-categories.update', $footwear), ['name' => 'Shoes'])->assertSessionHasNoErrors();

    expect($footwear->refresh()->name)->toBe('Shoes')
        ->and(suggestedLabels($this->workshopId, 'footwear'))->toBe(array_map('strval', $forty));

    // One size alone is stored as no row, and reads back as One size.
    $oneSize = sizeIdsIn($this->workshopId, ['One size']);
    actingAs($this->user)
        ->put(route('settings.product-categories.update', $footwear), ['name' => 'Shoes', 'suggested_sizes' => $oneSize])
        ->assertSessionHasNoErrors();

    expect(suggestedLabels($this->workshopId, 'footwear'))->toBe([])
        ->and($addProduct())->toBe($oneSize)
        ->and(categoryRow($this->user, 'footwear')['suggested_size_ids'])->toBe($oneSize)
        ->and(suggestedLabels($other->id, 'footwear'))->toHaveCount(11)
        ->and($productSizes())->toBe($before);
});

it('refuses a suggestion that is empty, mixes One size with others or names a size of another workshop, and saves nothing', function (Closure $payload, string $key, ?string $message) {
    $footwear = ProductCategory::query()->where('code', 'footwear')->firstOrFail();
    $mine = sizeIdsIn($this->workshopId, ['One size', 'S']);
    $foreign = sizeIdsIn(Workshop::factory()->create()->id, ['S'])[0];

    actingAs($this->user)
        ->put(route('settings.product-categories.update', $footwear), ['name' => 'Changed', 'suggested_sizes' => $payload($mine, $foreign)])
        ->assertSessionHasErrors($message === null ? [$key] : [$key => $message]);

    expect($footwear->refresh()->name)->toBe('Footwear')
        ->and(suggestedLabels($this->workshopId, 'footwear'))->toHaveCount(11);
})->with([
    'none chosen' => [fn (): array => [], 'suggested_sizes', 'Choose at least one size.'],
    'One size with others' => [fn (array $mine): array => $mine, 'suggested_sizes', "One size can't be combined with other sizes. Choose it alone, or choose other sizes."],
    "another workshop's size" => [fn (array $mine, int $foreign): array => [$foreign], 'suggested_sizes.0', null],
]);

it('writes the Suggests line of a category, and a category created in Settings suggests One size', function (array $labels, string $line) {
    actingAs($this->user)->post(route('settings.product-categories.store'), ['code' => 'elastic', 'name' => 'Elastic'])->assertSessionHasNoErrors();

    $oneSize = sizeIdsIn($this->workshopId, ['One size']);
    $row = categoryRow($this->user, 'elastic');
    expect($row['suggests'])->toBe('One size')
        ->and($row['suggested_size_ids'])->toBe($oneSize);

    actingAs($this->user)
        ->put(route('settings.product-categories.update', $row['id']), ['name' => 'Elastic', 'suggested_sizes' => sizeIdsIn($this->workshopId, $labels)])
        ->assertSessionHasNoErrors();

    expect(categoryRow($this->user, 'elastic'))->toMatchArray(['suggests' => $line, 'suggested_size_ids' => sizeIdsIn($this->workshopId, $labels)]);
})->with([
    'an unbroken run of seven' => [['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], 'XS to XXXL'],
    'an unbroken run of eleven' => [array_map('strval', range(36, 46)), '36 to 46'],
    'One size' => [['One size'], 'One size'],
    'four sizes' => [['S', 'M', 'L', 'XL'], 'S, M, L, XL'],
    'seven with a gap' => [['XS', 'S', 'M', 'XL', 'XXL', 'XXXL', '36'], 'XS, S, M and 4 more'],
]);

it("sends every size of the workshop, One size flagged and last, and each default category's suggestion", function () {
    $props = actingAs($this->user)->get(route('settings.product-categories.index'))->inertiaProps();
    $rows = array_column($props['categories'], null, 'code');

    expect($props['sizes'])->toHaveCount(19)
        ->and(array_keys($props['sizes'][0]))->toBe(['id', 'label', 'one_size'])
        ->and(array_column($props['sizes'], 'one_size'))->toBe([...array_fill(0, 18, false), true])
        ->and($rows['apparel']['suggests'])->toBe('XS to XXXL')
        ->and($rows['footwear']['suggests'])->toBe('36 to 46')
        ->and($rows['bag']['suggests'])->toBe('One size');
});
