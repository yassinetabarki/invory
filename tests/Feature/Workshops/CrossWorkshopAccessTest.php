<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| S-44-d, AC-3 and AC-4: another workshop's records are Not Found and can't be changed (ADR-0013)
|--------------------------------------------------------------------------
|
| The demo (`prepareDemo`, clock frozen) is the workshop of `test@example.com`. Sami has his own workshop with "Cotton canvas natural"
| and "Canvas Tote" (AC-2). Everything the demo's records need (ids, valid payloads) is read BEFORE signing in as Sami: the workshop scope
| would hide them from him, which is exactly what is proved here.
*/

beforeEach(function () {
    $this->demo = prepareDemo($this);
    Storage::fake('public');

    $workshop = Workshop::factory()->create();

    [$this->canvas, $this->tote] = CurrentWorkshop::run($workshop, function (): array {
        $canvas = Material::factory()->create(['sku' => 'B-CAN-001', 'name' => 'Cotton canvas natural', 'unit_cost' => '6.0000', 'quantity_on_hand' => '40.0000']);
        $tote = Product::factory()->withComponents([['material' => $canvas, 'quantity' => '0.8000']])->create([
            'sku' => 'B-TOT-001', 'name' => 'Canvas Tote', 'category' => 'bag', 'labor_cost' => '5.0000', 'overhead_cost' => '0.0000',
        ]);

        return [$canvas, $tote];
    });
    $this->sami = User::factory()->create(['workshop_id' => $workshop->id]);
});

/** A 1x1 PNG: a real image, so a photo upload passes the photo rules. */
function crossWorkshopPng(): string
{
    return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
}

/**
 * Every row of the seven tables of workshop data, of every workshop, plus the files of the public disk. Raw `DB::table()` reads, so the
 * workshop scope can't hide a change: two equal fingerprints mean nothing was written anywhere.
 *
 * @return array<string, mixed>
 */
function crossWorkshopFingerprint(): array
{
    $tables = [];

    foreach (['materials', 'products', 'product_components', 'stock_movements', 'sales', 'orders', 'order_lines'] as $table) {
        $tables[$table] = DB::table($table)->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all();
    }

    return [...$tables, 'files' => Storage::disk('public')->allFiles()];
}

it('answers 404 to every route with a record of another workshop, shows nothing of it and changes nothing (AC-3, AC-4)', function () {
    $records = [
        'material' => demoMaterial('MAT-DEN-012'),
        'product' => demoProduct('PRD-JKT-001'),
        'sale' => Sale::query()->where('product_id', pid(1))->orderBy('id')->firstOrFail(),
        'order' => bookOrder(),
    ];
    $secrets = ['Denim 12oz Indigo', 'MAT-DEN-012', 'Denim Jacket', 'PRD-JKT-001', 'Boutique Lina', '2 boxes'];

    // The demo's two photos exist on the disk, so a request that removed or replaced one would show.
    foreach (['material' => 'materials', 'product' => 'products'] as $kind => $directory) {
        Storage::disk('public')->put("{$directory}/demo-{$kind}.png", crossWorkshopPng());
        DB::table($directory)->where('id', $records[$kind]->id)->update(['photo_path' => "{$directory}/demo-{$kind}.png"]);
    }

    // The walk: every route behind `auth` with a bound record, every method (HEAD is GET's alias), with a VALID body for the writes
    // (so only the workshop can be the reason for the 404). Built before signing in as Sami: the payload helpers read the demo's records.
    $payloads = [
        'materials.update' => validMaterialPayload(['sku' => 'MAT-DEN-012']),
        'materials.restocks.store' => validRestockPayload(),
        'products.update' => validProductPayload(['sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket']),
        'sales.update' => validSalePayload(),
        'orders.update' => validOrderPayload(),
    ];
    $requests = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $parameters = $route->parameterNames();
        $behindAuth = array_filter($route->gatherMiddleware(), static fn (mixed $middleware): bool => is_string($middleware) && preg_match('/^auth(:|$)/', $middleware) === 1);

        if (array_intersect($parameters, array_keys($records)) === [] || $behindAuth === []) {
            continue;
        }

        $name = $route->getName() ?? $route->uri();
        $unmapped = array_diff($parameters, array_keys($records));
        $this->assertSame([], $unmapped, "The route [{$name}] has the parameter(s) [".implode(', ', $unmapped).'] that the cross-workshop walk cannot fill in: add each to `$records` (a record of the DEMO workshop) in tests/Feature/Workshops/CrossWorkshopAccessTest.php.');

        $uri = '/'.preg_replace_callback('/\{(\w+)\??\}/', static fn (array $match): string => (string) $records[$match[1]]->id, $route->uri());

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $payload = $payloads[$name] ?? [];
            $requests[] = [$method, $uri, $payload, $name];

            // The photo variants of a write: a new upload, and a removal.
            if ($method === 'PUT' && in_array($name, ['materials.update', 'products.update'], true)) {
                $requests[] = [$method, $uri, [...$payload, 'photo' => realUpload('new.png', crossWorkshopPng(), 'image/png')], "{$name} + photo"];
                $requests[] = [$method, $uri, [...$payload, 'remove_photo' => '1'], "{$name} + remove_photo"];
            }
        }
    }

    $before = crossWorkshopFingerprint();
    $this->actingAs($this->sami);

    foreach ($requests as [$method, $uri, $payload, $name]) {
        $response = $this->{strtolower($method)}($uri, $payload);
        $label = "{$method} {$uri} (route [{$name}])";

        expect($response->getStatusCode())->toBe(404, "{$label} must be Not Found for another workshop's record.");

        foreach ($secrets as $secret) {
            expect(stripos((string) $response->getContent(), $secret))->toBeFalse("{$label} shows [{$secret}] of the other workshop.");
        }

        expect(crossWorkshopFingerprint())->toBe($before, "{$label} changed something.");
    }

    // 14 routes carry a record (material show, edit, update, destroy, restock; product show, edit, update, destroy; sale update, destroy;
    // order edit, update, destroy): 22 requests, because an update answers to PUT and PATCH, and the material and product updates also run with a photo and with remove_photo.
    expect(count($requests))->toBeGreaterThanOrEqual(22);

    // Sami's Orders list and its search show nothing of Boutique Lina's order (AC-7).
    foreach ([[], ['search' => 'lina'], ['search' => 'PRD-JKT-001'], ['search' => 'jacket']] as $query) {
        expect(pageProp($this->get(route('orders.index', $query))->assertOk(), 'orders.data'))->toBe([]);
    }

    // The demo still shows what it showed: Denim Jacket at 35.7540, 112 units, 60 sales, Denim 12oz Indigo untouched.
    $this->actingAs($this->demo);
    $jacket = loadedPageProp($this->get(route('products.show', $records['product']))->assertOk(), 'product');
    $denim = pageProp($this->get(route('materials.show', $records['material']))->assertOk(), 'material');

    expect($jacket['cost']['unit_cost'])->toBe('35.7540')
        ->and($jacket['capacity']['max_producible'])->toBe(112)
        ->and($jacket['capacity']['bottleneck']['name'])->toBe('Metal shank button 17mm')
        ->and(pageProp($this->get(route('sales.index', ['period' => 'this_year'])), 'sales.meta.total'))->toBe(60)
        ->and(pageProp($this->get(route('orders.index')), 'orders.meta.total'))->toBe(1)
        ->and($denim)->toMatchArray(['quantity_on_hand' => '250.0000', 'unit_cost' => '8.5000'])
        ->and($denim['photo_url'])->toEndWith('materials/demo-material.png');
});

it('answers 404 to the demo workshop on the page of a record of Sami', function () {
    $this->actingAs($this->demo)->get(route('products.show', $this->tote))->assertNotFound();
});

// A composition, a sale: the id comes in the BODY, so the address is not what protects it. The error is the one for an id that does not exist.
it('refuses another workshop\'s id in a form like an id that does not exist, and saves nothing (AC-4)', function (string $case) {
    $demoMaterialId = demoMaterial('MAT-DEN-012')->id;
    $demoProductId = pid(1);
    $salePayload = validSalePayload();
    $toteLine = ['material_id' => $this->canvas->id, 'quantity' => '0.8', 'waste_percent' => '0'];
    $productPayload = ['sku' => 'B-TOT-001', 'name' => 'Canvas Tote', 'category' => 'bag', 'status' => 'active', 'labor_cost' => '5', 'overhead_cost' => '0'];

    // [what to send for a given id, the field that must carry the error, the id of the other workshop's record]
    [$send, $field, $theirId] = match ($case) {
        'a demo material in the composition of Canvas Tote' => [
            fn (int $id) => $this->put(route('products.update', $this->tote), [...$productPayload, 'components' => [$toteLine, ['material_id' => $id, 'quantity' => '1']]]),
            'components.1.material_id', $demoMaterialId,
        ],
        'a demo material in a new product' => [
            fn (int $id) => $this->post(route('products.store'), [...$productPayload, 'sku' => 'B-NEW-001', 'components' => [['material_id' => $id, 'quantity' => '1']]]),
            'components.0.material_id', $demoMaterialId,
        ],
        'a sale of the Denim Jacket' => [
            fn (int $id) => $this->post(route('sales.store'), [...$salePayload, 'product_id' => $id]),
            'product_id', $demoProductId,
        ],
    };

    $before = crossWorkshopFingerprint();
    $this->actingAs($this->sami);

    $theirs = $send($theirId);
    $theirs->assertSessionHasErrors($field);
    $theirMessage = session('errors')->first($field);

    $missing = $send(NO_SUCH_ID);
    $missing->assertSessionHasErrors($field);

    expect($theirMessage)->not->toBe('')->toBe(session('errors')->first($field))
        ->and(crossWorkshopFingerprint())->toBe($before);
})->with([
    'a demo material in the composition of Canvas Tote',
    'a demo material in a new product',
    'a sale of the Denim Jacket',
]);

it('stores a photo under an address its record does not give away (AC-3)', function () {
    $this->actingAs($this->sami);
    $png = crossWorkshopPng();

    $this->post(route('materials.store'), validMaterialPayload(['sku' => 'B-LIN-001', 'name' => 'Linen blend', 'photo' => realUpload('linen-blend.png', $png, 'image/png')]))
        ->assertSessionHasNoErrors();
    // A multipart body can't carry an empty composition: the key is left out.
    $this->post(route('products.store'), [
        'sku' => 'B-SHIRT-001', 'name' => 'Linen shirt', 'category' => 'apparel', 'status' => 'active', 'labor_cost' => '5', 'overhead_cost' => '0',
        'photo' => realUpload('linen-shirt.png', $png, 'image/png'),
    ])->assertSessionHasNoErrors();

    $material = Material::query()->where('sku', 'B-LIN-001')->firstOrFail();
    $product = Product::query()->where('sku', 'B-SHIRT-001')->firstOrFail();

    // 40 random characters after the folder: nothing to guess from. (The id is not searched as a substring: a one-digit id appears in
    // 40 random characters by chance, and the pattern already rules out an address built from it.)
    foreach ([[$material, 'materials'], [$product, 'products']] as [$record, $directory]) {
        expect($record->photo_path)->toMatch("#^{$directory}/[A-Za-z0-9]{40}\\.png$#")
            ->and(stripos($record->photo_path, $record->sku))->toBeFalse()
            ->and(stripos($record->photo_path, $record->name))->toBeFalse()
            ->and(Storage::disk('public')->exists($record->photo_path))->toBeTrue();
    }
});
