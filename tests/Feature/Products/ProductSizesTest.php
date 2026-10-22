<?php

use App\Models\User;
use Inertia\Inertia;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| S-58-b: the sizes reach the product page and the product form (ADR-0015)
|--------------------------------------------------------------------------
|
| A product has the sizes of its category. The page and Edit product receive them as `size_list`; Add and Edit product receive the
| list of every category as `category_sizes`. The figures of the page do not move. The demo workshop is the data of AC-2 and AC-5.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** @param  array<string, mixed>  $sizes  the `category_sizes` prop */
function expectDefaultCategorySizes(array $sizes): void
{
    $labels = fn (string $category): array => array_column($sizes[$category]['sizes'], 'label');

    expect($sizes['apparel']['name'])->toBe('S, M, L, XL')
        ->and($labels('apparel'))->toBe(['S', 'M', 'L', 'XL'])
        ->and($sizes['footwear']['name'])->toBe('36 to 46')
        ->and($labels('footwear'))->toBe(['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'])
        ->and($sizes['bag']['name'])->toBe('One size')
        ->and($labels('bag'))->toBe(['One size']);

    foreach (['accessory', 'jewelry', 'other'] as $category) {
        expect($sizes[$category]['name'])->toBe('One size');
    }
}

it('shows each demo product the sizes of its category and leaves its figures alone', function (int $number, string $listName, array $labels, ?array $figures) {
    $product = loadedPageProp(actingAs($this->user)->get(route('products.show', pid($number)))->assertOk(), 'product');

    expect($product['size_list']['name'])->toBe($listName)
        ->and(array_column($product['size_list']['sizes'], 'label'))->toBe($labels);

    if ($figures !== null) {
        expect([$product['cost']['unit_cost'], $product['capacity']['max_producible'], $product['capacity']['bottleneck']['name']])->toBe($figures);
    }
})->with([
    'Denim Jacket' => [1, 'S, M, L, XL', ['S', 'M', 'L', 'XL'], ['35.7540', 112, 'Metal shank button 17mm']],
    'Denim Jeans 5-pocket' => [5, 'S, M, L, XL', ['S', 'M', 'L', 'XL'], null],
    'Classic White T-shirt' => [2, 'S, M, L, XL', ['S', 'M', 'L', 'XL'], null],
    'Canvas Chore Jacket' => [6, 'S, M, L, XL', ['S', 'M', 'L', 'XL'], null],
    'Canvas Low Sneaker' => [3, '36 to 46', ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], null],
    'Leather Tote' => [4, 'One size', ['One size'], null],
    'Leather Belt' => [7, 'One size', ['One size'], null],
]);

it('sends the sizes of every category to Add product and Edit product, as an object even for a numeric code', function () {
    actingAs($this->user)
        ->post(route('settings.product-categories.store'), ['code' => '2027', 'name' => 'Season 2027'])
        ->assertSessionHasNoErrors();

    foreach ([route('products.create'), route('products.edit', pid(1))] as $url) {
        $response = actingAs($this->user)->get($url)->assertOk();
        $sizes = $response->inertiaProps('category_sizes');

        expectDefaultCategorySizes($sizes);
        expect($sizes['2027']['name'])->toBe('One size')
            ->and($sizes['2027']['sizes'])->toHaveCount(1);

        // The raw page, not the decoded array: the keys are all digits here, but a list would be sent as `[…]`.
        $raw = actingAs($this->user)
            ->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->assertOk()
            ->getContent();

        expect(json_decode($raw)->props->category_sizes)->toBeObject()
            ->and(json_decode($raw)->props->category_sizes->{'2027'}->name)->toBe('One size');
    }

    expect(actingAs($this->user)->get(route('products.edit', pid(1)))->inertiaProps('product.size_list.name'))->toBe('S, M, L, XL');
});

it('gives an account that has just signed up the same sizes on Add product', function () {
    /** @var TestCase $this */
    $this->post(route('register.store'), [
        'name' => 'Sami',
        'email' => 'sami@atelier-b.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $sami = User::query()->where('email', 'sami@atelier-b.test')->firstOrFail();
    $sami->markEmailAsVerified();

    $sizes = actingAs($sami->fresh())->get(route('products.create'))->assertOk()->inertiaProps('category_sizes');

    expectDefaultCategorySizes($sizes);
});
