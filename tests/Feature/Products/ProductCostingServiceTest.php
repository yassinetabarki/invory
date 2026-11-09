<?php

use App\Actions\Products\CreateProduct;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Services\Costing\ProductCostingService;
use Tests\Support\VariantJacket;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| ProductCostingService: Eloquent -> domain, end to end (S-04a)
|--------------------------------------------------------------------------
|
| The worked example of the invory-costing-domain skill, built through the factories and the CreateProduct Action,
| read back from the database and costed by the service. The maths itself is covered by tests/Unit/Domain/Costing.
*/

beforeEach(function () {
    $this->materials = workedExampleMaterials();
    $this->service = app(ProductCostingService::class);
});

it('costs the Denim Jacket of the worked example through Eloquent', function () {
    $costing = $this->service->cost(createDenimJacket($this->materials));

    $cost = $costing->result->cost;

    expect($cost->materialCost)->toBe('20.2540')
        ->and($cost->laborCost)->toBe('12.0000')
        ->and($cost->overheadCost)->toBe('3.5000')
        ->and($cost->unitCost)->toBe('35.7540')
        ->and($costing->margin)->toBeNull();

    expect(array_map(fn ($line) => [$line->materialName, $line->unit, $line->effectiveQuantity, $line->lineCost, $line->unitsPossible], $cost->lines))
        ->toBe([
            ['Denim 12oz Indigo', 'm', '1.9800', '16.8300', 126],
            ['Metal shank button 17mm', 'pc', '8.0000', '2.8000', 112],
            ['Polyester thread', 'm', '126.0000', '0.5040', 158],
            ['Woven brand label', 'pc', '1.0000', '0.1200', 400],
        ]);

    $capacity = $costing->result->capacity;

    expect($capacity->maxProducible)->toBe(112)
        ->and($capacity->bottleneckMaterialId)->toBe($this->materials['button']->id)
        ->and($capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm');
});

it('adds the margin when the product has a selling price', function () {
    $costing = $this->service->cost(createDenimJacket($this->materials, ['selling_price' => '89.0000']));

    expect($costing->margin->amount)->toBe('53.2460')
        ->and($costing->margin->percent)->toBe('59.83');
});

it('gives a zero-percent-safe margin for a free product and a negative one below cost', function (string $price, string $amount, ?string $percent) {
    $costing = $this->service->cost(createDenimJacket($this->materials, ['selling_price' => $price]));

    expect($costing->margin->amount)->toBe($amount)
        ->and($costing->margin->percent)->toBe($percent);
})->with([
    'free' => ['0.0000', '-35.7540', null],
    'below cost' => ['30.0000', '-5.7540', '-19.18'],
]);

it('gives the next limit of the bottleneck: without the button the Denim limits at 126', function () {
    $costing = $this->service->cost(createDenimJacket($this->materials));

    $next = $this->service->nextLimit($costing, $this->materials['button']->id);

    expect($next->maxProducible)->toBe(126)
        ->and($next->bottleneckMaterialId)->toBe($this->materials['denim']->id)
        ->and($next->bottleneckMaterialName)->toBe('Denim 12oz Indigo');
});

it('has no next limit for a material that is not the bottleneck', function () {
    $costing = $this->service->cost(createDenimJacket($this->materials));

    expect($this->service->nextLimit($costing, $this->materials['denim']->id))->toBeNull()
        ->and($this->service->nextLimit($costing, 999999))->toBeNull();
});

it('has no next limit when the bottleneck is the only material of the composition', function () {
    $product = Product::factory()->withComponents([
        ['material' => $this->materials['button'], 'quantity' => '8.0000'],
    ])->create();

    $costing = $this->service->cost(Product::query()->with('components.material')->findOrFail($product->id));

    expect($costing->result->capacity->bottleneckMaterialId)->toBe($this->materials['button']->id)
        ->and($this->service->nextLimit($costing, $this->materials['button']->id))->toBeNull();
});

it('cannot make a unit when the button stock is 7 (a unit needs 8)', function () {
    $this->materials['button']->update(['quantity_on_hand' => '7.0000']);

    $capacity = $this->service->cost(createDenimJacket($this->materials))->result->capacity;

    expect($capacity->maxProducible)->toBe(0)
        ->and($capacity->bottleneckMaterialId)->toBe($this->materials['button']->id);
});

it('costs a product without a composition: labor plus overhead, no capacity', function () {
    $product = app(CreateProduct::class)->handle(productData(), User::factory()->create());

    $costing = $this->service->cost(Product::query()->with('components.material')->findOrFail($product->id));

    expect($costing->result->cost->materialCost)->toBe('0.0000')
        ->and($costing->result->cost->unitCost)->toBe('15.5000')
        ->and($costing->result->cost->lines)->toBe([])
        ->and($costing->result->capacity->maxProducible)->toBeNull()
        ->and($costing->result->capacity->bottleneckMaterialId)->toBeNull()
        ->and($costing->bom)->toBe([]);
});

it('follows the stock and the price of the materials as they change', function () {
    $product = createDenimJacket($this->materials);

    $this->materials['button']->update(['quantity_on_hand' => '80.0000', 'unit_cost' => '0.5000']);

    $costing = $this->service->cost(Product::query()->with('components.material')->findOrFail($product->id));

    // 8 buttons at 0.5000 = 4.0000 instead of 2.8000.
    expect($costing->result->cost->materialCost)->toBe('21.4540')
        ->and($costing->result->capacity->maxProducible)->toBe(10);
});

it('maps the lines in display order with the material fields as domain input', function () {
    $lines = $this->service->bomLines(createDenimJacket($this->materials));

    expect(array_map(fn ($line) => $line->materialId, $lines))->toBe(array_map(fn (Material $m) => $m->id, array_values($this->materials)))
        ->and($lines[0]->unit)->toBe('m')
        ->and($lines[1]->quantity)->toBe('8.0000')
        ->and($lines[2]->wastePercent)->toBe('5.00')
        ->and($lines[3]->stockOnHand)->toBe('400.0000');
});

it('costs a soft-deleted product whose BOM uses a soft-deleted material', function () {
    $product = createDenimJacket($this->materials);
    $product->delete();
    $this->materials['button']->delete();

    $loaded = Product::withTrashed()->with('components.material')->findOrFail($product->id);

    expect($this->service->cost($loaded)->result->capacity->maxProducible)->toBe(112);
});

describe('relations must be loaded', function () {
    it('throws when the composition is not loaded', function () {
        $product = createDenimJacket($this->materials);

        $this->service->cost(Product::query()->findOrFail($product->id));
    })->throws(LogicException::class, 'load `components.material`');

    it('throws when a line has no material loaded', function () {
        $product = createDenimJacket($this->materials);

        $this->service->cost(Product::query()->with('components')->findOrFail($product->id));
    })->throws(LogicException::class, 'load `components.material`');
});

describe('batch costing', function () {
    it('costs many products with the same result as costing them one by one', function () {
        $first = createDenimJacket($this->materials, ['sku' => 'PRD-1']);
        $second = createDenimJacket($this->materials, ['sku' => 'PRD-2', 'selling_price' => '89.0000']);
        $empty = Product::factory()->create();

        $costings = $this->service->costMany(Product::query()->orderBy('id')->get());

        expect(array_keys($costings))->toBe([$first->id, $second->id, $empty->id])
            ->and($costings[$first->id]->result->cost->unitCost)->toBe('35.7540')
            ->and($costings[$second->id]->margin->percent)->toBe('59.83')
            ->and($costings[$empty->id]->result->capacity->maxProducible)->toBeNull();
    });

    it('runs the same number of queries for 3 products and for 30', function () {
        Product::factory()->count(3)->withComponents(4)->create();
        $few = Product::query()->get();

        $queriesForThree = countQueries(fn () => $this->service->costMany($few));

        Product::factory()->count(27)->withComponents(4)->create();
        $many = Product::query()->get();

        $queriesForThirty = countQueries(fn () => $this->service->costMany($many));

        // components, materials, then the category and the unit of those materials.
        expect($queriesForThree)->toBe(4)
            ->and($queriesForThirty)->toBe(4)
            ->and(count($this->service->costMany($many)))->toBe(30);
    });

    it('runs no query when the compositions are already loaded', function () {
        Product::factory()->count(3)->withComponents(2)->create();
        $products = Product::query()->with('components.material')->get();

        expect(countQueries(fn () => $this->service->costMany($products)))->toBe(0);
    });

    it('accepts any iterable and an empty one', function () {
        expect($this->service->costMany([]))->toBe([])
            ->and($this->service->costMany((fn () => yield from [])()))->toBe([]);
    });
});

describe('per colour and size (INV-16-d)', function () {
    it('costs the Denim Jacket of the card per colour and size: 16 guaranteed, 112 best, 33.8840 to 39.4940', function () {
        $jacket = VariantJacket::create();

        $costing = $this->service->cost($jacket->fresh());

        $variants = $costing->variants;
        $capacity = $costing->result->capacity;

        // The interim single figures are the binding variant's: Black XL, limited by the Black denim (40 / 2.42 = 16, never rounded up).
        expect($costing->varies())->toBeTrue()
            ->and($capacity->maxProducible)->toBe(16)
            ->and($capacity->bottleneckMaterialId)->toBe($jacket->materials['black_denim']->id)
            ->and($capacity->bottleneckMaterialName)->toBe('Denim 12oz Black')
            ->and([$variants->bindingColourId, $variants->bindingSizeId])->toBe([$jacket->colours['Black'], $jacket->sizes['XL']])
            ->and([$variants->guaranteed, $variants->best, $costing->best()])->toBe([16, 112, 112])
            ->and([$variants->bestColourId, $variants->bestSizeId, $variants->bestMaterialName])->toBe([$jacket->colours['Indigo'], $jacket->sizes['S'], 'Metal shank button 17mm'])
            ->and([$variants->unitCostMin, $variants->unitCostMax])->toBe(['33.8840', '39.4940'])
            ->and([$variants->marginAmountMin, $variants->marginAmountMax, $variants->marginPercentMin, $variants->marginPercentMax])->toBe(['49.5060', '55.1160', '55.62', '61.93'])
            ->and($costing->result->cost->unitCost)->toBe('39.4940')
            ->and([$costing->margin->amount, $costing->margin->percent])->toBe(['49.5060', '55.62'])
            ->and($costing->cantProduce())->toBeFalse()
            ->and(array_map(fn ($line) => $line->materialName, $costing->bom))->toBe(['Denim 12oz Black', 'Metal shank button 17mm', 'Polyester thread', 'Woven brand label'])
            ->and($this->service->nextLimit($costing, $jacket->materials['black_denim']->id))->toBeNull();
    });

    it('gives a colour without cells only the Same for every variant lines: Light wash costs 18.9240 and makes 112', function () {
        $jacket = VariantJacket::create();
        $jacket->product->colours()->attach($jacket->colours['Light wash'], ['workshop_id' => $jacket->workshop->id]);

        $variants = $this->service->cost($jacket->fresh())->variants;
        $lightWash = array_values(array_filter($variants->variants, fn ($variant) => $variant->colourId === $jacket->colours['Light wash']));

        expect($lightWash)->toHaveCount(4)
            ->and(array_map(fn ($variant) => [$variant->unitCost, $variant->maxProducible], $lightWash))->toBe(array_fill(0, 4, ['18.9240', 112]))
            ->and([$variants->unitCostMin, $variants->unitCostMax])->toBe(['18.9240', '39.4940'])
            ->and([$variants->guaranteed, $variants->best])->toBe([16, 112]);
    });

    it('keeps the figures of a product with no by-colour-and-size line, on the fast path', function () {
        $costing = $this->service->cost(createDenimJacket($this->materials));

        expect($costing->variants)->toBeNull()
            ->and($costing->varies())->toBeFalse()
            ->and($costing->result->cost->unitCost)->toBe('35.7540')
            ->and($costing->result->capacity->maxProducible)->toBe(112)
            ->and($costing->best())->toBe(112)
            ->and($costing->result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm')
            ->and($costing->cantProduce())->toBeFalse();
    });

    it('counts a varying product as unproducible only when its best is 0, in the list filter too', function () {
        $jacket = VariantJacket::create();
        $user = User::factory()->create();
        $listed = fn (): array => pageProp(actingAs($user)->get(route('products.index', ['cant_produce' => 1])), 'products.data.*.sku') ?? [];

        // Black denim out: 0 guaranteed, but the Indigo variants still make 112.
        $jacket->materials['black_denim']->update(['quantity_on_hand' => '0.0000']);
        $costing = $this->service->cost($jacket->fresh());

        expect([$costing->result->capacity->maxProducible, $costing->best(), $costing->cantProduce()])->toBe([0, 112, false])
            ->and($listed())->toBe([]);

        // Every material it uses out: no variant can be made.
        foreach ($jacket->materials as $material) {
            $material->update(['quantity_on_hand' => '0.0000']);
        }

        $costing = $this->service->cost($jacket->fresh());

        expect([$costing->result->capacity->maxProducible, $costing->best(), $costing->cantProduce()])->toBe([0, 0, true])
            ->and($listed())->toBe([$jacket->product->sku]);
    });

    it('runs the same number of queries for 10 products that vary by colour and size as for 1', function () {
        $jackets = array_map(fn () => VariantJacket::create(), range(1, 12));
        $load = fn (array $some) => Product::query()->whereKey(array_map(fn (VariantJacket $jacket) => $jacket->product->id, $some))->get();
        $products = fn (array $some) => $load($some);

        // The sizes of a product are read once per request: every measurement takes products no earlier one touched.
        $this->service->costMany($products([$jackets[0]])); // warm-up

        $one = $products([$jackets[1]]);
        $ten = $products(array_slice($jackets, 2, 10));

        $queriesForOne = countQueries(fn () => $this->service->costMany($one));
        $queriesForTen = countQueries(fn () => $this->service->costMany($ten));

        // components, materials (+ their category and unit), colours, cells, sizes.
        expect($queriesForOne)->toBe($queriesForTen)
            ->and(count($this->service->costMany($ten)))->toBe(10);
    });
});
