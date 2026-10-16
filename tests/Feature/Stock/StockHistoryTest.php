<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\StockHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The history of a material carries the order and the product of a cutting entry (S-53-a, AC-4 and AC-5)
|--------------------------------------------------------------------------
|
| The client, the product's name and the units are joined in by StockHistory, so the page costs the same queries however many
| cutting entries it holds, and an order or a product deleted since still reads.
*/

/** A cutting (or return) of 30 (5) Denim Jackets for Boutique Lina taken out of (given back to) the material, by `$actor`. */
function cuttingEntry(Material $material, StockMovementType $type, User $actor, array $attributes = []): StockMovement
{
    return StockMovement::factory()->for($material)->create([
        'type' => $type,
        'occurred_on' => '2026-10-16',
        'quantity' => $type === StockMovementType::Cutting ? '59.4000' : '9.9000',
        'quantity_before' => $type === StockMovementType::Cutting ? '250.0000' : '190.6000',
        'quantity_after' => $type === StockMovementType::Cutting ? '190.6000' : '200.5000',
        'unit_price' => null,
        'units' => $type === StockMovementType::Cutting ? 30 : 5,
        'user_id' => $actor->id,
        ...$attributes,
    ]);
}

it('sends a cutting and a return with the units, the order, and the product, in one query that reads deleted ones too', function () {
    $user = User::factory()->create(['name' => 'Yassine']);
    $material = Material::factory()->create(['name' => 'Denim 12oz Indigo']);
    $order = Order::factory()->create(['client' => 'Boutique Lina']);
    $product = Product::factory()->create(['name' => 'Denim Jacket']);
    $refs = ['order_id' => $order->id, 'product_id' => $product->id];

    $cutting = cuttingEntry($material, StockMovementType::Cutting, $user, $refs);
    $return = cuttingEntry($material, StockMovementType::CuttingReturn, $user, $refs);
    $url = route('materials.show', $material);
    $entry = fn ($movement, string $type, string $label, string $quantity, int $units): array => [
        'id' => $movement->id, 'type' => $type, 'type_label' => $label, 'quantity' => $quantity, 'units' => $units,
        'order' => ['id' => $order->id, 'client' => 'Boutique Lina'], 'product_name' => 'Denim Jacket', 'user_name' => 'Yassine',
    ];
    $expected = [$entry($return, 'cutting_return', 'Back from cutting', '9.9000', 5), $entry($cutting, 'cutting', 'Cutting', '59.4000', 30)];
    $read = fn (): array => array_map(
        fn (array $movement): array => array_intersect_key($movement, $expected[0]),
        pageProp(actingAs($user)->get($url)->assertOk(), 'history'),
    );

    expect($read())->toBe($expected);

    // The order and the product are deleted afterwards: the entries keep reading them (they are only ever soft-deleted).
    $order->delete();
    $product->delete();

    expect($read())->toBe($expected);

    // One query for the history however many cutting entries there are: the order and product come in the same read.
    $few = queryLog(fn () => $this->get($url)->assertOk());

    foreach (range(1, 15) as $unused) {
        cuttingEntry($material, StockMovementType::Cutting, $user, ['order_id' => Order::factory()->create()->id, 'product_id' => Product::factory()->create()->id]);
    }

    $many = queryLog(fn () => $this->get($url)->assertOk());

    expect(count($many))->toBe(count($few), implode(PHP_EOL, $many))
        ->and(queriesMatching($many, 'from "stock_movements"'))->toHaveCount(1);

    // The index page's window query (recent_history) reads them the same way.
    $recent = app(StockHistory::class)->recentFor([$material->id], 1)[0];

    expect($recent->type)->toBe(StockMovementType::Cutting)
        ->and($recent->order_client)->toBeString()
        ->and($recent->product_name)->toBeString()
        ->and($recent->units)->toBe(30);
});
