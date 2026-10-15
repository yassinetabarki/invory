<?php

use App\Actions\Materials\CreateMaterial;
use App\Actions\Products\CreateProduct;
use App\Actions\Sales\RecordSale;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;

/*
|--------------------------------------------------------------------------
| S-44-c, AC-6: the data belongs to the workshop, not to the person (ADR-0013 decision 12)
|--------------------------------------------------------------------------
|
| Two accounts in ONE workshop (today only the data move of S-44-b creates this; inviting people comes later): what one records, the
| other sees at once, with the name of who did it. Deleting an account deletes only the person.
*/

beforeEach(function () {
    $this->travelTo(DEMO_NOW);
    $this->workshop = Workshop::factory()->create();
    [$this->first, $this->second] = User::factory()->count(2)->create(['workshop_id' => $this->workshop->id]);
});

/**
 * A material, a product made of it and a sale of that product, all recorded by `$actor` through the Actions (so every audit column
 * of the three, and the "Created" history entry, points at the actor): what is left when that account is deleted.
 */
function recordWorkshopData(Workshop $workshop, User $actor): void
{
    CurrentWorkshop::run($workshop, function () use ($actor): void {
        $material = app(CreateMaterial::class)->handle(validMaterialPayload(), $actor);
        $product = app(CreateProduct::class)->handle(
            productData(['components' => [['material_id' => $material->id, 'quantity' => '1.5000', 'waste_percent' => '0.00']]]),
            $actor,
        );
        app(RecordSale::class)->handle(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '89.0000', 'sold_on' => '2026-10-08'], $actor);
    });
}

it('shows an account what the other account of its workshop adds, and who did it (AC-6)', function () {
    actingAs($this->first)->post(route('materials.store'), validMaterialPayload(['sku' => 'SHARED-001', 'name' => 'Shared canvas']))
        ->assertSessionHasNoErrors();

    $material = actingAs($this->second)->get(route('materials.index'))->assertOk();
    $show = $this->get(route('materials.show', Material::query()->where('sku', 'SHARED-001')->firstOrFail()))->assertOk();

    expect(pageProp($material, 'materials.data.*.name'))->toBe(['Shared canvas'])
        ->and(pageProp($show, 'history.*.user_name'))->toBe([$this->first->name]);
});

it('keeps every material, product and sale of the workshop when an account deletes itself, for the account that stays', function () {
    recordWorkshopData($this->workshop, $this->first);

    actingAs($this->first)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

    assertGuest();
    expect(User::query()->whereKey($this->first->id)->exists())->toBeFalse();

    $materials = actingAs($this->second)->get(route('materials.index'))->assertOk();
    $products = $this->get(route('products.index'))->assertOk();
    $sales = $this->get(route('sales.index'))->assertOk();

    expect(pageProp($materials, 'materials.meta.total'))->toBe(1)
        ->and(pageProp($products, 'products.meta.total'))->toBe(1)
        ->and(pageProp($sales, 'sales.meta.total'))->toBe(1)
        // Who recorded them is gone with the person, not the records.
        ->and(Sale::query()->value('user_id'))->toBeNull()
        ->and(Material::query()->without(['unit', 'category'])->value('created_by'))->toBeNull()
        ->and(StockMovement::query()->value('user_id'))->toBeNull();
});

it('keeps the data of a workshop whose only account is deleted', function () {
    recordWorkshopData($this->workshop, $this->first);
    $this->second->delete();

    actingAs($this->first)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

    // Nobody is signed in and no workshop is set (system context), so these counts see every workshop: the one workshop of this test.
    assertGuest();
    expect(CurrentWorkshop::id())->toBeNull()
        ->and(User::query()->where('workshop_id', $this->workshop->id)->count())->toBe(0)
        ->and(Workshop::query()->whereKey($this->workshop->id)->exists())->toBeTrue()
        ->and(Material::query()->count())->toBe(1)
        ->and(Product::query()->count())->toBe(1)
        ->and(Sale::query()->count())->toBe(1);
});
