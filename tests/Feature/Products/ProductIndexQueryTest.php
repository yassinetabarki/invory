<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The products list: SQL filters, the order, and the "can't produce" interim (S-04b)
|--------------------------------------------------------------------------
|
| Search, category and status are SQL conditions and the page is cut in SQL. "Can't produce" needs the costing, so the filtered set
| is costed in PHP and paginated in memory (accepted until S-23 adds `product_metrics`): this file pins that the two paths agree.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    // A clean slate: the demo sales, products and materials go (a product with sales can't be hard-deleted), the tests make the ones they need.
    Sale::query()->delete();
    Product::query()->forceDelete();
    Material::query()->forceDelete();
});

/** The names of the products on the list page of a URL. */
function listedNames(string $url): array
{
    return pageProp(actingAs(test()->user)->get($url), 'products.data.*.name');
}

it('orders by name case-insensitively, then by id', function () {
    foreach (['banana', 'Apple', 'cherry', 'apple', 'Banana', 'APPLE'] as $name) {
        Product::factory()->create(['name' => $name]);
    }

    // The three apples tie on the lower-cased name: the first created comes first. Likewise the two bananas.
    expect(listedNames(route('products.index')))->toBe(['Apple', 'apple', 'APPLE', 'banana', 'Banana', 'cherry']);
});

it('leaves soft-deleted products out of the list and of its total', function () {
    Product::factory()->create(['name' => 'Kept']);
    Product::factory()->create(['name' => 'Gone'])->delete();

    $response = actingAs($this->user)->get(route('products.index'));

    expect(pageProp($response, 'products.data.*.name'))->toBe(['Kept'])
        ->and(pageProp($response, 'products.meta.total'))->toBe(1);
});

it('paginates in SQL with the filters in the links', function () {
    config(['invory.per_page' => 2]);
    foreach (range(1, 5) as $n) {
        Product::factory()->create(['name' => "Shirt {$n}", 'category' => 'apparel', 'status' => 'active']);
    }
    Product::factory()->create(['name' => 'Shoe', 'category' => 'footwear', 'status' => 'active']);
    Product::factory()->draft()->create(['name' => 'Shirt draft', 'category' => 'apparel']);

    $response = actingAs($this->user)->get(route('products.index', ['search' => 'shirt', 'category' => 'apparel', 'status' => 'active', 'page' => 2]));

    expect(pageProp($response, 'products.data.*.name'))->toBe(['Shirt 3', 'Shirt 4'])
        ->and(pageProp($response, 'products.meta.total'))->toBe(5)
        ->and(pageProp($response, 'products.meta.last_page'))->toBe(3)
        ->and(pageProp($response, 'products.links.prev'))->toContain('search=shirt')->toContain('category=apparel')->toContain('status=active')->toContain('page=1')
        ->and(pageProp($response, 'products.links.next'))->toContain('page=3');
});

describe('cant_produce', function () {
    beforeEach(function () {
        $this->materials = workedExampleMaterials(buttonStock: '0');
        $this->lines = workedExampleLines($this->materials);
    });

    it('lists the products that can\'t be made, by name, and nothing else', function () {
        // The button is out of stock, so every jacket can't be made; the others use the label only.
        foreach (['Jacket B', 'Jacket A'] as $name) {
            createDenimJacket($this->materials, ['sku' => "SKU-{$name}", 'name' => $name]);
        }
        Product::factory()->withComponents([['material' => $this->materials['label'], 'quantity' => '1']])->create(['name' => 'Label only']);
        Product::factory()->create(['name' => 'No composition']);

        expect(listedNames(route('products.index', ['cant_produce' => 1])))->toBe(['Jacket A', 'Jacket B'])
            ->and(listedNames(route('products.index')))->toBe(['Jacket A', 'Jacket B', 'Label only', 'No composition']);
    });

    it('paginates the matches in memory like the SQL path does', function () {
        config(['invory.per_page' => 2]);
        foreach (['E', 'C', 'A', 'D', 'B'] as $name) {
            createDenimJacket($this->materials, ['sku' => "SKU-{$name}", 'name' => "Jacket {$name}"]);
        }
        Product::factory()->withComponents([['material' => $this->materials['label'], 'quantity' => '1']])->create(['name' => 'Fine']);

        $first = actingAs($this->user)->get(route('products.index', ['cant_produce' => 1]));
        $second = actingAs($this->user)->get(route('products.index', ['cant_produce' => 1, 'page' => 2]));
        $last = actingAs($this->user)->get(route('products.index', ['cant_produce' => 1, 'page' => 3]));
        $beyond = actingAs($this->user)->get(route('products.index', ['cant_produce' => 1, 'page' => 4]));

        expect(pageProp($first, 'products.data.*.name'))->toBe(['Jacket A', 'Jacket B'])
            ->and(pageProp($second, 'products.data.*.name'))->toBe(['Jacket C', 'Jacket D'])
            ->and(pageProp($last, 'products.data.*.name'))->toBe(['Jacket E'])
            ->and(pageProp($beyond, 'products.data'))->toBe([])
            // The same meta as a SQL page: totals count the matches only, the path is the list's, the filters stay in the links.
            ->and(pageProp($second, 'products.meta'))->toMatchArray([
                'current_page' => 2, 'from' => 3, 'to' => 4, 'last_page' => 3, 'per_page' => 2, 'total' => 5, 'path' => route('products.index'),
            ])
            ->and(pageProp($second, 'products.links.next'))->toContain('cant_produce=1')->toContain('page=3')
            ->and(pageProp($second, 'products.links.prev'))->toContain('cant_produce=1')->toContain('page=1')
            ->and(pageProp($last, 'products.links.next'))->toBeNull()
            ->and(pageProp($beyond, 'products.meta.total'))->toBe(5);
    });

    it('combines with the SQL filters', function () {
        createDenimJacket($this->materials, ['sku' => 'SKU-A', 'name' => 'Denim A', 'category' => 'apparel', 'status' => 'active']);
        createDenimJacket($this->materials, ['sku' => 'SKU-B', 'name' => 'Denim B', 'category' => 'apparel', 'status' => 'draft']);
        createDenimJacket($this->materials, ['sku' => 'SKU-C', 'name' => 'Denim C', 'category' => 'bag', 'status' => 'active']);
        createDenimJacket($this->materials, ['sku' => 'SKU-D', 'name' => 'Other D', 'category' => 'apparel', 'status' => 'active']);

        expect(listedNames(route('products.index', ['cant_produce' => 1, 'search' => 'denim', 'category' => 'apparel', 'status' => 'active'])))->toBe(['Denim A']);
    });

    it('follows the stock: restocking the bottleneck takes the products off the list', function () {
        createDenimJacket($this->materials, ['sku' => 'SKU-A', 'name' => 'Denim A']);

        expect(listedNames(route('products.index', ['cant_produce' => 1])))->toBe(['Denim A']);

        Material::query()->findOrFail($this->materials['button']->id)->update(['quantity_on_hand' => '900']);

        expect(listedNames(route('products.index', ['cant_produce' => 1])))->toBe([]);
    });
});
