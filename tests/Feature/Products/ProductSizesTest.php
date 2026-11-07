<?php

use App\Models\Size;
use App\Models\User;
use Inertia\Inertia;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| S-58-b: the sizes reach the product page and the product form (ADR-0015)
|--------------------------------------------------------------------------
|
| A product has its own sizes. The page and Edit product receive them as `sizes`, `one_size` and `sizes_line`; Add and Edit product receive
| the ids every category suggests as `category_sizes`. The figures of the page do not move. The demo workshop is the data of AC-2 and AC-5.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * The default suggestions as labels: the ids of `category_sizes` looked up in the page's own `sizes` (so it also holds for another workshop).
 *
 * @param  array<string, mixed>  $categorySizes  the `category_sizes` prop
 * @param  list<array{id: int, label: string}>  $options  the `sizes` prop
 */
function expectDefaultCategorySizes(array $categorySizes, array $options): void
{
    $names = array_column($options, 'label', 'id');
    $labels = fn (string $category): array => array_map(fn (int $id): string => $names[$id], $categorySizes[$category]);

    expect($labels('apparel'))->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
        ->and($labels('footwear'))->toBe(['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']);

    foreach (['bag', 'accessory', 'jewelry', 'other'] as $category) {
        expect($labels($category))->toBe(['One size']);
    }
}

it('shows each demo product its own sizes and leaves its figures alone', function (int $number, string $line, bool $oneSize, array $labels, ?array $figures) {
    $product = loadedPageProp(actingAs($this->user)->get(route('products.show', pid($number)))->assertOk(), 'product');

    expect($product['sizes_line'])->toBe($line)
        ->and($product['one_size'])->toBe($oneSize)
        ->and(array_column($product['sizes'], 'label'))->toBe($labels)
        ->and($product)->not->toHaveKey('size_list');

    if ($figures !== null) {
        expect([$product['cost']['unit_cost'], $product['capacity']['max_producible'], $product['capacity']['bottleneck']['name']])->toBe($figures);
    }
})->with([
    'Denim Jacket' => [1, 'Sizes XS to XXXL', false, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], ['35.7540', 112, 'Metal shank button 17mm']],
    'Denim Jeans 5-pocket' => [5, 'Sizes XS to XXXL', false, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], null],
    'Classic White T-shirt' => [2, 'Sizes XS to XXXL', false, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], null],
    'Canvas Chore Jacket' => [6, 'Sizes XS to XXXL', false, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], null],
    'Canvas Low Sneaker' => [3, 'Sizes 36 to 46', false, ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], null],
    'Leather Tote' => [4, 'One size', true, ['One size'], null],
    'Leather Belt' => [7, 'One size', true, ['One size'], null],
]);

it('sends the ids every category suggests to Add product and Edit product, as an object even for a numeric code', function () {
    actingAs($this->user)
        ->post(route('settings.product-categories.store'), ['code' => '2027', 'name' => 'Season 2027'])
        ->assertSessionHasNoErrors();

    foreach ([route('products.create'), route('products.edit', pid(1))] as $url) {
        $response = actingAs($this->user)->get($url)->assertOk();
        $sizes = $response->inertiaProps('category_sizes');

        expectDefaultCategorySizes($sizes, $response->inertiaProps('sizes'));
        expect($sizes['2027'])->toBe([Size::query()->where('is_one_size', true)->value('id')]);

        // The raw page, not the decoded array: the keys are all digits here, but a list would be sent as `[…]`.
        $raw = actingAs($this->user)
            ->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->assertOk()
            ->getContent();

        expect(json_decode($raw)->props->category_sizes)->toBeObject()
            ->and(json_decode($raw)->props->category_sizes->{'2027'})->toBe([Size::query()->where('is_one_size', true)->value('id')]);
    }

    expect(actingAs($this->user)->get(route('products.edit', pid(1)))->inertiaProps('product.sizes_line'))->toBe('Sizes XS to XXXL');
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

    $response = actingAs($sami->fresh())->get(route('products.create'))->assertOk();

    expectDefaultCategorySizes($response->inertiaProps('category_sizes'), $response->inertiaProps('sizes'));
});
