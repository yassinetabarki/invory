<?php

use App\Enums\ProductStatus;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Gate;

describe('Product', function () {
    it('casts money to scale-4 strings and the status to the enum', function () {
        $product = Product::factory()->create(['labor_cost' => '12', 'overhead_cost' => '3.5', 'selling_price' => '89', 'status' => 'draft']);
        $product = Product::query()->findOrFail($product->id);

        expect($product->labor_cost)->toBe('12.0000')
            ->and($product->overhead_cost)->toBe('3.5000')
            ->and($product->selling_price)->toBe('89.0000')
            ->and($product->status)->toBe(ProductStatus::Draft);
    });

    it('keeps a missing selling price as null', function () {
        $product = Product::factory()->withoutSellingPrice()->create();

        expect(Product::query()->findOrFail($product->id)->selling_price)->toBeNull();
    });

    it('relates to its category by id, whatever the code the factory got', function () {
        $product = Product::factory()->create(['category' => 'footwear']);

        expect($product->category->code)->toBe('footwear')
            ->and($product->category->is(ProductCategory::query()->where('code', 'footwear')->first()))->toBeTrue();
    });

    it('can be created with an id, a model or a custom category', function () {
        $custom = ProductCategory::factory()->create();

        expect(Product::factory()->create(['category' => $custom])->category_id)->toBe($custom->id)
            ->and(Product::factory()->create(['category_id' => $custom->id])->category_id)->toBe($custom->id);
    });

    it('fails clearly on an unknown category code', function () {
        Product::factory()->create(['category' => 'nope']);
    })->throws(InvalidArgumentException::class, 'No product category with code [nope]');

    it('has a soft delete and an SKU the factory keeps unique', function () {
        $products = Product::factory()->count(20)->create();

        expect($products->pluck('sku')->unique())->toHaveCount(20);

        $products->first()->delete();

        expect(Product::query()->count())->toBe(19)
            ->and(Product::withTrashed()->count())->toBe(20);
    });

    it('only mass-assigns the product fields, not the audit columns or the photo', function () {
        expect(fn () => new Product(['sku' => 'X', 'created_by' => 1]))->toThrow(MassAssignmentException::class)
            ->and(fn () => new Product(['sku' => 'X', 'photo_path' => 'x.jpg']))->toThrow(MassAssignmentException::class);
    });

    it('lists its creator and updater', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $product->forceFill(['created_by' => $user->id])->save();

        expect($product->creator->is($user))->toBeTrue()
            ->and($product->updater)->toBeNull();
    });
});

describe('ProductComponent', function () {
    it('casts quantity and waste to strings and orders a product\'s lines by position', function () {
        $product = Product::factory()->create();
        $second = ProductComponent::factory()->create(['product_id' => $product->id, 'quantity' => '2', 'waste_percent' => '5', 'position' => 1]);
        $first = ProductComponent::factory()->create(['product_id' => $product->id, 'quantity' => '1.25', 'waste_percent' => '0', 'position' => 0]);

        $lines = $product->components()->get();

        expect($lines->modelKeys())->toBe([$first->id, $second->id])
            ->and($lines[0]->quantity)->toBe('1.2500')
            ->and($lines[0]->waste_percent)->toBe('0.00')
            ->and($lines[1]->quantity)->toBe('2.0000')
            ->and($lines[1]->waste_percent)->toBe('5.00');
    });

    it('breaks a position tie by id', function () {
        $product = Product::factory()->create();
        $a = ProductComponent::factory()->create(['product_id' => $product->id, 'position' => 3]);
        $b = ProductComponent::factory()->create(['product_id' => $product->id, 'position' => 3]);

        expect($product->components()->get()->modelKeys())->toBe([$a->id, $b->id]);
    });

    it('belongs to its product and material, and is reachable from the material', function () {
        $material = Material::factory()->create();
        $product = Product::factory()->withComponents([['material' => $material, 'quantity' => '1']])->create();
        $line = $product->components()->firstOrFail();

        expect($line->product->is($product))->toBeTrue()
            ->and($line->material->is($material))->toBeTrue()
            ->and($material->components()->pluck('id')->all())->toBe([$line->id]);
    });

    it('still reaches a soft-deleted material', function () {
        $material = Material::factory()->create();
        $line = ProductComponent::factory()->create(['material_id' => $material->id]);
        $material->delete();

        expect($line->fresh()->material->is($material))->toBeTrue();
    });
});

describe('ProductFactory::withComponents', function () {
    it('creates that many materials and lines, in order, with valid quantities', function () {
        $product = Product::factory()->withComponents(3)->create();
        $lines = $product->components()->get();

        expect($lines)->toHaveCount(3)
            ->and($lines->pluck('position')->all())->toBe([0, 1, 2])
            ->and($lines->pluck('material_id')->unique())->toHaveCount(3)
            ->and(Material::query()->count())->toBe(3);

        foreach ($lines as $line) {
            expect(BigDecimal::of($line->quantity)->isPositive())->toBeTrue()
                ->and(BigDecimal::of($line->waste_percent)->isLessThan(100))->toBeTrue();
        }
    });

    it('uses the given materials and values', function () {
        $denim = Material::factory()->create();
        $button = Material::factory()->create();

        $product = Product::factory()->withComponents([
            ['material' => $denim, 'quantity' => '1.8', 'waste_percent' => '10', 'notes' => 'Body'],
            ['material' => $button->id, 'quantity' => '8'],
        ])->create();

        expect($product->components()->get()->map(fn ($l) => [$l->material_id, $l->quantity, $l->waste_percent, $l->notes, $l->position])->all())
            ->toBe([
                [$denim->id, '1.8000', '10.00', 'Body', 0],
                [$button->id, '8.0000', '0.00', null, 1],
            ]);
    });

    it('works for several products at once', function () {
        Product::factory()->count(3)->withComponents(2)->create();

        expect(ProductComponent::query()->count())->toBe(6);
    });
});

describe('ProductPolicy', function () {
    it('lets every signed-in user do everything, as MaterialPolicy does', function (string $ability) {
        $user = User::factory()->create();
        $subject = in_array($ability, ['viewAny', 'create'], true) ? Product::class : Product::factory()->create();

        expect(Gate::forUser($user)->allows($ability, $subject))->toBeTrue();
    })->with(['viewAny', 'view', 'create', 'update', 'delete']);

    it('denies a guest', function () {
        expect(Gate::allows('viewAny', Product::class))->toBeFalse();
    });
});
