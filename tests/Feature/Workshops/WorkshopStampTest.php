<?php

use App\Actions\Materials\AppendStockMovement;
use App\Actions\Materials\CreateMaterial;
use App\Actions\Products\CreateProduct;
use App\Actions\Products\SyncProductComponents;
use App\Actions\Sales\RecordSale;
use App\Enums\StockMovementType;
use App\Exceptions\MissingWorkshopException;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/*
|--------------------------------------------------------------------------
| S-44-b: who stamps `workshop_id` on a new record (ADR-0013)
|--------------------------------------------------------------------------
|
| Reads are filtered since S-44-c, so a scenario that looks a record up does it as that record's workshop. Each scenario gets `$mine` (the workshop of `$actor`) and `$other`, and checks its own result.
*/

it('stamps every owned record with the right workshop', function (Closure $scenario) {
    $mine = Workshop::factory()->create();
    $other = Workshop::factory()->create();
    $actor = User::factory()->create(['workshop_id' => $mine->id]);

    $scenario($mine, $other, $actor);
})->with([
    'a root record takes the current workshop' => [function (Workshop $mine, Workshop $other): void {
        // `workshop_id` is left empty on purpose: the creating hook fills it.
        [$material, $product] = CurrentWorkshop::run($other, fn (): array => [
            Material::factory()->create(['workshop_id' => null]),
            Product::factory()->create(['workshop_id' => null]),
        ]);

        expect($material->workshop_id)->toBe($other->id)
            ->and($product->workshop_id)->toBe($other->id)
            ->and(DB::table('materials')->where('id', $material->id)->value('workshop_id'))->toBe($other->id);
    }],

    'the create Actions take the workshop of the actor, whichever one is current' => [function (Workshop $mine, Workshop $other, User $actor): void {
        $material = CurrentWorkshop::run($other, fn (): Material => app(CreateMaterial::class)->handle(validMaterialPayload(), $actor));
        $product = CurrentWorkshop::run($other, fn (): Product => app(CreateProduct::class)->handle(
            productData(['components' => [['material_id' => $material->id, 'quantity' => '1.5', 'waste_percent' => '0']]]),
            $actor,
        ));

        expect($material->workshop_id)->toBe($mine->id)
            ->and($product->workshop_id)->toBe($mine->id)
            // The composition line, and the "Created" entry of the material's history, follow their parent.
            ->and($product->components()->pluck('workshop_id')->all())->toBe([$mine->id])
            ->and($material->stockMovements()->pluck('workshop_id')->all())->toBe([$mine->id]);
    }],

    'a composition line, a movement and a sale take the workshop of their parent' => [function (Workshop $mine, Workshop $other, User $actor): void {
        $product = Product::factory()->create(['workshop_id' => $other->id]);
        $material = Material::factory()->create(['workshop_id' => $other->id]);

        // The actor and the current workshop are `mine`: the parent's workshop wins over both.
        $movement = CurrentWorkshop::run($mine, function () use ($product, $material, $actor): StockMovement {
            app(SyncProductComponents::class)->handle($product, [['material_id' => $material->id, 'quantity' => '2.0000']]);

            return app(AppendStockMovement::class)->handle($material, StockMovementType::Restock, '2026-10-08', $actor, ['quantity' => '5.0000', 'quantity_after' => '5.0000']);
        });

        // A sale finds its product through the workshop scope (S-44-c), so the product's workshop is the current one; the actor is still `mine`.
        $sale = CurrentWorkshop::run($other, fn (): Sale => app(RecordSale::class)->handle(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10.0000', 'sold_on' => '2026-10-08'], $actor));

        expect($product->components()->pluck('workshop_id')->all())->toBe([$other->id])
            ->and($movement->workshop_id)->toBe($other->id)
            ->and($sale->workshop_id)->toBe($other->id);
    }],

    'an order takes the current workshop' => [function (Workshop $mine, Workshop $other): void {
        $order = CurrentWorkshop::run($other, fn (): Order => Order::factory()->create(['workshop_id' => null]));

        expect($order->workshop_id)->toBe($other->id)
            ->and(DB::table('orders')->where('id', $order->id)->value('workshop_id'))->toBe($other->id);
    }],

    'an order line takes the workshop of its order' => [function (Workshop $mine, Workshop $other): void {
        $order = Order::factory()->create(['workshop_id' => $other->id]);
        $product = Product::factory()->create(['workshop_id' => $other->id]);

        // No current workshop: a line that did not take its order's would be refused for having none.
        $line = OrderLine::factory()->for($order)->for($product)->create();

        expect($line->workshop_id)->toBe($other->id);
    }],

    'a cut takes the workshop of its line' => [function (Workshop $mine, Workshop $other): void {
        $line = OrderLine::factory()->create(['order_id' => Order::factory()->create(['workshop_id' => $other->id])->id]);

        // No current workshop: a cut that did not take its line's would be refused for having none.
        $cut = OrderLineCut::factory()->create(['order_line_id' => $line->id]);

        expect($cut->workshop_id)->toBe($other->id)
            ->and(DB::table('order_line_cuts')->where('id', $cut->id)->value('workshop_id'))->toBe($other->id);
    }],

    'a cut material takes the workshop of its cut' => [function (Workshop $mine, Workshop $other): void {
        $cut = OrderLineCut::factory()->create(['order_line_id' => OrderLine::factory()->create(['order_id' => Order::factory()->create(['workshop_id' => $other->id])->id])->id]);
        $material = Material::factory()->create(['workshop_id' => $other->id]);

        $cutMaterial = OrderLineCutMaterial::factory()->create(['order_line_cut_id' => $cut->id, 'material_id' => $material->id]);

        expect($cut->workshop_id)->toBe($other->id)
            ->and($cutMaterial->workshop_id)->toBe($other->id);
    }],

    'a root record with no workshop anywhere is refused' => [function (): void {
        // Nobody is signed in and no workshop was set.
        expect(CurrentWorkshop::id())->toBeNull()
            ->and(fn () => Material::factory()->create(['workshop_id' => null]))->toThrow(MissingWorkshopException::class, "can't be saved without a workshop");
    }],
]);
