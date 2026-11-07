<?php

use App\Models\Product;
use App\Services\Products\ProductSizes;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| The one reader of a product's sizes (INV-18-a, ADR-0028)
|--------------------------------------------------------------------------
|
| Every screen and save asks `ProductSizes` which sizes a product has. Proved here: the order and the One size flag, one read per
| request (alone or in a batch), and `fresh()` reading again. The readers themselves are covered by the stock, barcode and restock
| tests, which pass unchanged.
*/

beforeEach(function () {
    prepareDemo($this);
    // Seeding reads sizes too: every test starts on a request that has read nothing yet.
    app()->instance('request', Request::create('/'));
});

/** The queries a callback runs on a request that has read nothing yet. */
function queriesOnNewRequest(Closure $callback): int
{
    app()->instance('request', Request::create('/'));

    return countQueries($callback);
}

it('gives the sizes of a product in list order, and whether it is One size', function (string $sku, array $labels, bool $oneSize) {
    $set = app(ProductSizes::class)->of(demoProduct($sku));

    expect($set->sizes->pluck('label')->all())->toBe($labels)
        ->and($set->oneSize)->toBe($oneSize)
        ->and($set->ids())->toBe($set->sizes->pluck('id')->all())
        ->and($set->has($set->ids()[0]))->toBeTrue()
        ->and($set->has(0))->toBeFalse();
})->with([
    'Denim Jacket (apparel)' => ['PRD-JKT-001', ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], false],
    'Canvas Low Sneaker (footwear)' => ['PRD-SNK-003', ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], false],
    'Leather Tote (bag)' => ['PRD-TOT-004', ['One size'], true],
]);

it('reads a product once per request, alone or in a batch', function () {
    $sizes = app(ProductSizes::class);
    [$jacket, $jeans, $tote] = [demoProduct('PRD-JKT-001'), demoProduct('PRD-JNS-005'), demoProduct('PRD-TOT-004')];

    // Alone: the second ask costs nothing and is the very same answer.
    $first = $again = null;
    $firstQueries = queriesOnNewRequest(function () use ($sizes, $jacket, &$first, &$again) {
        $first = $sizes->of($jacket);
        $again = $sizes->of($jacket);
    });
    expect($firstQueries)->toBeGreaterThan(0)
        ->and($again)->toBe($first)
        ->and(countQueries(fn () => $sizes->of($jacket)))->toBe(0);

    // In a batch (3 products, 2 categories): as many queries as for one product, keyed by product id, and the cache is filled.
    $oneProduct = queriesOnNewRequest(fn () => $sizes->forProducts([$jacket]));
    $batch = [];
    $batchQueries = queriesOnNewRequest(function () use ($sizes, $jacket, $jeans, $tote, &$batch) {
        $batch = $sizes->forProducts([$jacket, $jeans, $tote]);
    });

    expect($batchQueries)->toBe($oneProduct)
        ->and(array_keys($batch))->toBe([$jacket->id, $jeans->id, $tote->id])
        ->and($batch[$jacket->id]->ids())->toBe($batch[$jeans->id]->ids())
        ->and($batch[$tote->id]->oneSize)->toBeTrue()
        ->and(countQueries(fn () => $sizes->of($tote)))->toBe(0)
        ->and($sizes->of($tote))->toBe($batch[$tote->id]);
});

it('reads again when a writer asks for fresh sizes, and keeps that answer', function () {
    $sizes = app(ProductSizes::class);
    $jacket = Product::query()->where('sku', 'PRD-JKT-001')->firstOrFail();
    $cached = $sizes->of($jacket);
    $fresh = null;

    $queries = countQueries(function () use ($sizes, $jacket, &$fresh) {
        $fresh = $sizes->fresh($jacket);
    });

    expect($queries)->toBeGreaterThan(0)
        ->and($fresh)->not->toBe($cached)
        ->and($fresh->ids())->toBe($cached->ids())
        ->and($sizes->of($jacket))->toBe($fresh);
});
