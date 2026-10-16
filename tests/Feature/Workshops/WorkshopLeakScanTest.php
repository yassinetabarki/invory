<?php

use App\Enums\SalesPeriod;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| S-44-c: the leak scan (ADR-0013 decision 10)
|--------------------------------------------------------------------------
|
| Two workshops, each with rows in every dashboard panel and a recognisable marker (`ZZ-ALPHA`, `ZZ-BRAVO`) in every name, SKU, note and
| reference. The walk then goes through the ROUTER: every GET route behind `auth`, requested as a user of one workshop, fails when the
| other workshop's marker appears anywhere in the response: the Inertia props with every deferred prop loaded, or the raw body of a
| route that is not Inertia (`materials.options`). A page or route added later that forgets the workshop filter fails here without
| anyone adding a test for it.
|
| The search variants use the marker WITHOUT its last letter (`ZZ-BRAV`): it still matches the other workshop's rows, but the page
| echoing the search term back in its `filters` doesn't count as a leak.
*/

/** The only parameters a walked GET route may have, each answered with the scanning user's OWN record. */
const LEAK_SCAN_PARAMETERS = ['material', 'product', 'order'];

/**
 * Why a GET route behind `auth` is not walked, or null when it is. Every skip is a route that shows no workshop data.
 */
function leakScanSkipReason(RoutingRoute $route): ?string
{
    $middleware = $route->gatherMiddleware();

    return match (true) {
        in_array(RequirePassword::class, $middleware, true), in_array('password.confirm', $middleware, true) => 'needs a password confirmation first (security settings, two-factor and passkey set-up)',
        $route->uri() === 'settings' => 'only redirects to settings/profile',
        $route->getName() === 'verification.verify' => 'the signed link of the verification e-mail: needs a hash, shows no data',
        $route->getName() === 'passkey.confirm-options' => 'the WebAuthn challenge of a passkey confirmation: JSON with no data of the workshop',
        default => null,
    };
}

/**
 * Every GET route behind `auth` as [route, skip reason].
 *
 * @return list<array{RoutingRoute, string|null}>
 */
function leakScanRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $behindAuth = array_filter($route->gatherMiddleware(), static fn (mixed $middleware): bool => is_string($middleware) && preg_match('/^auth(:|$)/', $middleware) === 1);

        if (in_array('GET', $route->methods(), true) && $behindAuth !== []) {
            $routes[] = [$route, leakScanSkipReason($route)];
        }
    }

    return $routes;
}

/**
 * One workshop with a user and every kind of row the pages list: a material in stock, an out-of-stock one, a product that can be made
 * (and sold, in the last 30 days, with a marked note), a product that can't be made (its material is out of stock) and a marked
 * stock-history entry, and an order of the product. Every name, SKU, note, reference and client carries the marker. The marked material and
 * product also hold a photo, under
 * a random address that does NOT carry the marker (like a real upload): the scan looks for the address itself.
 *
 * @return array{user: User, material: Material, outOfStock: Material, product: Product, unproducible: Product, sale: Sale, order: Order, photos: list<string>}
 */
function leakScanWorkshop(string $marker): array
{
    $workshop = Workshop::factory()->create();
    $photos = ['materials/'.Str::random(40).'.png', 'products/'.Str::random(40).'.png'];

    return CurrentWorkshop::run($workshop, function () use ($workshop, $marker, $photos): array {
        $material = Material::factory()->create([
            'sku' => "{$marker}-MAT-1", 'name' => "{$marker} canvas", 'notes' => "{$marker} note",
            'unit_cost' => '6.0000', 'quantity_on_hand' => '100.0000', 'reorder_level' => '10.0000',
            'photo_path' => $photos[0],
        ]);
        $outOfStock = Material::factory()->outOfStock()->create(['sku' => "{$marker}-MAT-2", 'name' => "{$marker} zipper"]);

        $product = Product::factory()->withComponents([['material' => $material, 'quantity' => '0.5000']])->create(['sku' => "{$marker}-PRD-1", 'name' => "{$marker} tote", 'photo_path' => $photos[1]]);
        $unproducible = Product::factory()->withComponents([['material' => $outOfStock, 'quantity' => '1.0000']])->create(['sku' => "{$marker}-PRD-2", 'name' => "{$marker} pouch"]);

        $user = User::factory()->create(['workshop_id' => $workshop->id, 'name' => "Owner of {$marker}"]);
        StockMovement::factory()->restock('100.0000', '100.0000', '6.0000', "{$marker}-PO-1")->create(['material_id' => $material->id, 'user_id' => $user->id]);
        $sale = Sale::factory()->create(['product_id' => $product->id, 'note' => "{$marker} walk-in"]);
        $order = Order::factory()->create(['client' => "{$marker} Boutique", 'note' => "{$marker} deliver in two boxes"]);
        OrderLine::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 12]);

        return compact('user', 'material', 'outOfStock', 'product', 'unproducible', 'sale', 'order', 'photos');
    });
}

/**
 * The text a response shows: the Inertia props with every deferred prop loaded, or the raw body of any other response.
 *
 * @param  TestResponse<Response>  $response
 */
function leakScanText(TestResponse $response): string
{
    $original = $response->original;

    if ($original instanceof View && array_key_exists('page', $original->getData())) {
        // loadedPageProps() needs at least one deferred prop on the page. The list sits in the raw page: `inertiaPage()` drops it.
        $props = empty($original->getData()['page']['deferredProps'] ?? null) ? $response->inertiaProps() : loadedPageProps($response);

        // Slashes stay as they are: a photo address (`materials/abc.png`) would otherwise be encoded as `materials\/abc.png` and never match.
        return json_encode($props, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    return (string) $response->getContent();
}

it('shows no record of the other workshop on any signed-in page', function (string $mine, string $theirs) {
    $workshops = ['ZZ-ALPHA' => leakScanWorkshop('ZZ-ALPHA'), 'ZZ-BRAVO' => leakScanWorkshop('ZZ-BRAVO')];
    [$own, $other] = [$workshops[$mine], $workshops[$theirs]];

    $parameters = ['material' => $own['material']->id, 'product' => $own['product']->id, 'order' => $own['order']->id];
    // A prefix of the other marker: it matches that workshop's rows, and a page echoing it back still doesn't contain the marker itself.
    $echoSafeSearch = ['search' => substr($theirs, 0, -1)];

    // Every variant is requested on the routes it belongs to: [route name => list of query strings].
    $variants = [
        'materials.index' => [['low_stock' => 1], [...$echoSafeSearch, 'low_stock' => 1]],
        'materials.options' => [['include' => [$other['material']->id, $other['outOfStock']->id]]],
        'products.index' => [['cant_produce' => 1], [...$echoSafeSearch, 'cant_produce' => 1]],
        'sales.index' => [['product_id' => $other['product']->id], ['product_id' => $other['product']->id, 'period' => SalesPeriod::ThisYear->value]],
        'dashboard' => array_map(static fn (SalesPeriod $period): array => ['period' => $period->value], SalesPeriod::cases()),
    ];

    $walked = [];
    $ownSeenOn = [];
    $ownPhotoSeenOn = [];
    $this->actingAs($own['user']);

    foreach (leakScanRoutes() as [$route, $skipReason]) {
        if ($skipReason !== null) {
            continue;
        }

        $name = $route->getName() ?? $route->uri();
        $unknown = array_diff($route->parameterNames(), LEAK_SCAN_PARAMETERS);
        $this->assertSame([], $unknown, "The route [{$name}] has the parameter(s) [".implode(', ', $unknown).'] that the leak scan cannot fill in: add each to LEAK_SCAN_PARAMETERS and to $parameters (the id of the scanning user\'s OWN record), or skip the route in leakScanSkipReason() with the reason.');

        $uri = '/'.preg_replace_callback('/\{(\w+)\??\}/', fn (array $match): string => (string) $parameters[$match[1]], $route->uri());

        // Plain, a search for the other marker, then the route's own variants.
        $queries = [[], $echoSafeSearch, ...($variants[$name] ?? [])];

        foreach ($queries as $query) {
            $response = $this->get($uri.($query === [] ? '' : '?'.http_build_query($query)));
            $text = leakScanText($response);

            $this->assertFalse(stripos($text, $theirs), "GET {$uri} ".json_encode($query)." (route [{$name}]) shows the other workshop's marker [{$theirs}]: it leaks.");

            foreach ($other['photos'] as $photo) {
                $this->assertFalse(str_contains($text, $photo), "GET {$uri} ".json_encode($query)." (route [{$name}]) shows the other workshop's photo [{$photo}]: it leaks.");
            }

            if (str_contains($text, $mine)) {
                $ownSeenOn[$name] = true;
            }

            if (str_contains($text, $own['photos'][0]) || str_contains($text, $own['photos'][1])) {
                $ownPhotoSeenOn[$name] = true;
            }
        }

        $walked[] = $name;
    }

    // The scan is not vacuous: the pages that list or show records do show the scanning workshop's own marker.
    expect(array_keys($ownSeenOn))->toContain('dashboard', 'materials.index', 'materials.show', 'materials.edit', 'materials.options', 'products.index', 'products.show', 'products.edit', 'sales.index', 'orders.index', 'orders.create', 'orders.show', 'orders.edit');
    // ... and so do the photos of its own material and product, so the photo search isn't vacuous either.
    expect(array_keys($ownPhotoSeenOn))->toContain('materials.index', 'materials.show', 'products.index', 'products.show', 'sales.index');
    expect($walked)->not->toBeEmpty();
})->with([
    'ZZ-ALPHA looks for ZZ-BRAVO' => ['ZZ-ALPHA', 'ZZ-BRAVO'],
    'ZZ-BRAVO looks for ZZ-ALPHA' => ['ZZ-BRAVO', 'ZZ-ALPHA'],
]);

// ADR-0013 decision 4: with no signed-in user there is no workshop, and the scope then adds no filter. So a route that reads workshop data
// must never be reachable without `auth`.
it('puts every page and endpoint of the application behind auth', function () {
    $unprotected = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $action = $route->getActionName();
        $isApplicationRoute = str_starts_with($action, 'App\\Http\\Controllers\\') || ($action === 'Inertia\Controller' && $route->getName() !== 'home');
        $behindAuth = array_filter($route->gatherMiddleware(), static fn (mixed $middleware): bool => is_string($middleware) && preg_match('/^auth(:|$)/', $middleware) === 1);

        if ($isApplicationRoute && $behindAuth === []) {
            $unprotected[] = implode('|', $route->methods()).' /'.$route->uri();
        }
    }

    $this->assertSame([], $unprotected, 'These routes have no `auth` middleware, so they would run with no workshop and no filter (ADR-0013 decision 4): '.implode(', ', $unprotected));
});
