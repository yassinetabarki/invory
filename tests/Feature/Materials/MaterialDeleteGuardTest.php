<?php

use App\Actions\Materials\DeleteMaterial;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\SyncProductComponents;
use App\Exceptions\MaterialInUseException;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertNotSoftDeleted;
use function Pest\Laravel\assertSoftDeleted;

/*
|--------------------------------------------------------------------------
| A material used by a non-deleted product can't be deleted (S-04a, ARCHITECTURE §4 "Deletion rules")
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->material = Material::factory()->create(['name' => 'Denim 12oz Indigo']);
});

/** A product with a one-line composition on the material. */
function productUsing(Material $material, array $attributes = []): Product
{
    return Product::factory()->withComponents([['material' => $material, 'quantity' => '1.5']])->create($attributes);
}

describe('DeleteMaterial', function () {
    it('throws MaterialInUseException naming the products, sorted, and keeps the material', function () {
        productUsing($this->material, ['name' => 'Zip Jacket']);
        productUsing($this->material, ['name' => 'Denim Jacket']);
        productUsing($this->material, ['name' => 'apron']);

        try {
            app(DeleteMaterial::class)->handle($this->material);
            $this->fail('The material is in use, deleting it must throw.');
        } catch (MaterialInUseException $exception) {
            expect($exception->productNames)->toBe(['apron', 'Denim Jacket', 'Zip Jacket'])
                ->and($exception->getMessage())->toBe("Denim 12oz Indigo is used in the composition of apron, Denim Jacket, Zip Jacket, so it can't be deleted. Remove it from there first.");
        }

        assertNotSoftDeleted($this->material);
    });

    it('lists the first five products and counts the rest in the message, but carries every name', function () {
        foreach (['G', 'F', 'E', 'D', 'C', 'B', 'A'] as $name) {
            productUsing($this->material, ['name' => "Product {$name}"]);
        }

        try {
            app(DeleteMaterial::class)->handle($this->material);
        } catch (MaterialInUseException $exception) {
            expect($exception->productNames)->toHaveCount(7)
                ->and($exception->getMessage())->toContain('Product A, Product B, Product C, Product D, Product E and 2 more')
                ->and($exception->getMessage())->not->toContain('Product F');
        }
    });

    it('deletes a material that no product uses', function () {
        app(DeleteMaterial::class)->handle($this->material);

        assertSoftDeleted($this->material);
    });

    it('ignores the products that use other materials', function () {
        productUsing(Material::factory()->create());

        app(DeleteMaterial::class)->handle($this->material);

        assertSoftDeleted($this->material);
    });

    it('counts a product of any status, draft and archived included', function (string $state) {
        Product::factory()->{$state}()->withComponents([['material' => $this->material, 'quantity' => '1']])->create();

        expect(fn () => app(DeleteMaterial::class)->handle($this->material))->toThrow(MaterialInUseException::class);
    })->with(['draft', 'archived']);

    it('allows the delete when only a soft-deleted product uses the material', function () {
        $product = productUsing($this->material);
        app(DeleteProduct::class)->handle($product);

        app(DeleteMaterial::class)->handle($this->material);

        assertSoftDeleted($this->material);
        // The deleted product keeps its line (history, restore).
        expect($product->components()->count())->toBe(1);
    });

    it('lists only the live products when a deleted one uses the material too', function () {
        productUsing($this->material, ['name' => 'Gone']);
        Product::query()->where('name', 'Gone')->firstOrFail()->delete();
        productUsing($this->material, ['name' => 'Live']);

        try {
            app(DeleteMaterial::class)->handle($this->material);
        } catch (MaterialInUseException $exception) {
            expect($exception->productNames)->toBe(['Live']);
        }
    });

    it('allows the delete once the line is removed from the composition', function () {
        $product = productUsing($this->material);

        expect(fn () => app(DeleteMaterial::class)->handle($this->material))->toThrow(MaterialInUseException::class);

        app(SyncProductComponents::class)->handle($product, []);
        app(DeleteMaterial::class)->handle($this->material);

        assertSoftDeleted($this->material);
    });

    it('keeps the line of a deleted product readable after the material is deleted', function () {
        $product = productUsing($this->material);
        $product->delete();
        app(DeleteMaterial::class)->handle($this->material);

        // The soft-deleted material stays reachable from the line of the soft-deleted product (history, restore).
        expect(Product::withTrashed()->with('components.material')->find($product->id)->components->first()->material->is($this->material))->toBeTrue();
    });
});

describe('DELETE materials.destroy', function () {
    it('shows an error toast naming the products, redirects back and keeps the material', function () {
        productUsing($this->material, ['name' => 'Denim Jacket']);
        productUsing($this->material, ['name' => 'Chore Coat']);

        actingAs($this->user)
            ->from(route('materials.show', $this->material))
            ->delete(route('materials.destroy', $this->material))
            ->assertRedirect(route('materials.show', $this->material))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => "Denim 12oz Indigo is used in the composition of Chore Coat, Denim Jacket, so it can't be deleted. Remove it from there first.",
            ]);

        assertNotSoftDeleted($this->material);
        expect(Material::query()->count())->toBe(1);
    });

    it('deletes a material that only a soft-deleted product used, with the success toast', function () {
        app(DeleteProduct::class)->handle(productUsing($this->material));

        actingAs($this->user)
            ->delete(route('materials.destroy', $this->material))
            ->assertRedirect(route('materials.index'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material deleted.']);

        assertSoftDeleted($this->material);
    });
});
