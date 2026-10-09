<?php

use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\SyncProductComponents;
use App\Actions\Products\UpdateProduct;
use App\Enums\ProductStatus;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->materials = workedExampleMaterials();
    $this->user = User::factory()->create();
    $this->create = app(CreateProduct::class);
    $this->update = app(UpdateProduct::class);
});

/** The lines of a product as [material id, quantity, waste, notes, position], in display order. */
function storedLines(Product $product): array
{
    return ProductComponent::query()
        ->where('product_id', $product->id)
        ->orderBy('position')
        ->get()
        ->map(fn (ProductComponent $line) => [$line->material_id, $line->quantity, $line->waste_percent, $line->notes, $line->position])
        ->all();
}

describe('CreateProduct', function () {
    it('creates the product and its composition', function () {
        $product = $this->create->handle(
            productData(['selling_price' => '89', 'components' => workedExampleLines($this->materials)]),
            $this->user,
        );

        expect($product->exists)->toBeTrue()
            ->and($product->sku)->toBe('PRD-DJ-001')
            ->and($product->name)->toBe('Denim Jacket')
            ->and($product->status)->toBe(ProductStatus::Active)
            ->and($product->category_id)->toBe(ProductCategory::query()->where('code', 'apparel')->value('id'))
            ->and($product->labor_cost)->toBe('12.0000')
            ->and($product->overhead_cost)->toBe('3.5000')
            ->and($product->selling_price)->toBe('89.0000')
            ->and(storedLines($product))->toBe([
                [$this->materials['denim']->id, '1.8000', '10.00', 'Body', 0],
                [$this->materials['button']->id, '8.0000', '0.00', 'Front', 1],
                [$this->materials['thread']->id, '120.0000', '5.00', 'Seams', 2],
                [$this->materials['label']->id, '1.0000', '0.00', 'Neck', 3],
            ]);
    });

    it('creates a product without a composition, a selling price or a description', function () {
        $product = $this->create->handle(productData(['selling_price' => null, 'description' => null]), $this->user);

        expect($product->selling_price)->toBeNull()
            ->and($product->description)->toBeNull()
            ->and(ProductComponent::query()->count())->toBe(0);
    });

    it('uses the database defaults for what the input leaves out', function () {
        $product = $this->create->handle(
            ['sku' => 'PRD-MIN', 'name' => 'Minimal', 'category' => 'bag'],
            $this->user,
        );

        expect($product->status)->toBe(ProductStatus::Active)
            ->and($product->labor_cost)->toBe('0.0000')
            ->and($product->overhead_cost)->toBe('0.0000')
            ->and($product->selling_price)->toBeNull();
    });

    it('defaults a missing waste to 0.00 and keeps the notes optional', function () {
        $product = $this->create->handle(
            productData(['components' => [['material_id' => $this->materials['denim']->id, 'quantity' => '2']]]),
            $this->user,
        );

        expect(storedLines($product))->toBe([[$this->materials['denim']->id, '2.0000', '0.00', null, 0]]);
    });

    it('records the acting user as creator and updater, never from the input', function () {
        $someoneElse = User::factory()->create();

        $product = $this->create->handle(productData(['components' => []]), $this->user);

        expect($product->created_by)->toBe($this->user->id)
            ->and($product->updated_by)->toBe($this->user->id)
            ->and($product->creator->is($this->user))->toBeTrue()
            ->and($product->updater->is($this->user))->toBeTrue();

        // The audit columns are not mass-assignable: a request can't choose them.
        expect(fn () => $this->create->handle(productData(['sku' => 'PRD-X', 'created_by' => $someoneElse->id]), $this->user))
            ->toThrow(MassAssignmentException::class);
    });

    it('writes nothing when a line fails: no product, no lines', function () {
        $lines = [
            ['material_id' => $this->materials['denim']->id, 'quantity' => '1.8'],
            ['material_id' => $this->materials['button']->id, 'quantity' => '8'],
            ['material_id' => $this->materials['denim']->id, 'quantity' => '2'],
        ];

        expect(fn () => $this->create->handle(productData(['components' => $lines]), $this->user))
            ->toThrow(QueryException::class);

        assertDatabaseCount('products', 0);
        assertDatabaseCount('product_components', 0);
    });

    it('writes nothing when a line points at a material that does not exist', function () {
        $lines = [
            ['material_id' => $this->materials['denim']->id, 'quantity' => '1.8'],
            ['material_id' => 999999, 'quantity' => '1'],
        ];

        expect(fn () => $this->create->handle(productData(['components' => $lines]), $this->user))
            ->toThrow(QueryException::class);

        assertDatabaseCount('products', 0);
        assertDatabaseCount('product_components', 0);
    });

    it('rejects a second active product with the same SKU, and accepts it once the first is deleted', function () {
        $first = $this->create->handle(productData(), $this->user);

        expect(fn () => $this->create->handle(productData(), $this->user))->toThrow(QueryException::class);

        $first->delete();

        expect($this->create->handle(productData(), $this->user)->exists)->toBeTrue();
    });
});

describe('UpdateProduct', function () {
    beforeEach(function () {
        $this->creator = User::factory()->create();
        $this->product = $this->create->handle(
            productData(['components' => workedExampleLines($this->materials)]),
            $this->creator,
        );
    });

    it('updates the product fields and the audit trail', function () {
        $updated = $this->update->handle($this->product, [
            'name' => 'Denim Jacket v2',
            'category' => 'accessory',
            'status' => 'archived',
            'labor_cost' => '14',
            'selling_price' => '99.5',
        ], $this->user);

        expect($updated->name)->toBe('Denim Jacket v2')
            ->and($updated->status)->toBe(ProductStatus::Archived)
            ->and($updated->category_id)->toBe(ProductCategory::query()->where('code', 'accessory')->value('id'))
            ->and($updated->labor_cost)->toBe('14.0000')
            ->and($updated->overhead_cost)->toBe('3.5000')
            ->and($updated->selling_price)->toBe('99.5000')
            ->and($updated->created_by)->toBe($this->creator->id)
            ->and($updated->updated_by)->toBe($this->user->id);
    });

    it('leaves the composition alone when the input has no components key', function () {
        $before = storedLines($this->product);

        $this->update->handle($this->product, ['name' => 'Renamed'], $this->user);

        expect(storedLines($this->product))->toBe($before);
    });

    it('leaves the composition alone when the components are null (a multipart body cannot carry an empty list)', function () {
        $before = storedLines($this->product);

        $this->update->handle($this->product, ['name' => 'Renamed', 'components' => null], $this->user);

        expect(storedLines($this->product))->toBe($before);
    });

    it('replaces the composition: updates, adds, removes and reorders lines', function () {
        $denimLineId = ProductComponent::query()->where('material_id', $this->materials['denim']->id)->value('id');
        $buttonLineId = ProductComponent::query()->where('material_id', $this->materials['button']->id)->value('id');

        $this->update->handle($this->product, [
            'components' => [
                // The label moves to the front, the thread is gone, the button stays with new numbers,
                // and a new material would come last (none here: the denim changes in place).
                ['material_id' => $this->materials['label']->id, 'quantity' => '2', 'waste_percent' => '1.5', 'notes' => 'Collar'],
                ['material_id' => $this->materials['button']->id, 'quantity' => '6', 'waste_percent' => '0', 'notes' => null],
                ['material_id' => $this->materials['denim']->id, 'quantity' => '1.7', 'waste_percent' => '12', 'notes' => 'Body'],
            ],
        ], $this->user);

        expect(storedLines($this->product))->toBe([
            [$this->materials['label']->id, '2.0000', '1.50', 'Collar', 0],
            [$this->materials['button']->id, '6.0000', '0.00', null, 1],
            [$this->materials['denim']->id, '1.7000', '12.00', 'Body', 2],
        ]);

        // Lines are matched by material and updated in place: the same rows, not delete + insert.
        expect(ProductComponent::query()->find($denimLineId)->quantity)->toBe('1.7000')
            ->and(ProductComponent::query()->find($buttonLineId)->quantity)->toBe('6.0000');
    });

    it('adds a line for a new material', function () {
        $newMaterial = Material::factory()->create();

        $this->update->handle($this->product, [
            'components' => [
                ...workedExampleLines($this->materials),
                ['material_id' => $newMaterial->id, 'quantity' => '0.5'],
            ],
        ], $this->user);

        expect(storedLines($this->product))->toHaveCount(5)
            ->and(storedLines($this->product)[4])->toBe([$newMaterial->id, '0.5000', '0.00', null, 4]);
    });

    it('clears the composition when an empty list is submitted', function () {
        $this->update->handle($this->product, ['components' => []], $this->user);

        expect(storedLines($this->product))->toBe([]);
    });

    it('only touches its own product', function () {
        $other = $this->create->handle(
            productData(['sku' => 'PRD-OTHER', 'components' => workedExampleLines($this->materials)]),
            $this->user,
        );

        $this->update->handle($this->product, ['components' => []], $this->user);

        expect(storedLines($other))->toHaveCount(4);
    });

    it('keeps the old product and composition when the sync fails halfway', function () {
        $before = storedLines($this->product);

        expect(fn () => $this->update->handle($this->product, [
            'name' => 'Should not stick',
            'components' => [
                ['material_id' => $this->materials['label']->id, 'quantity' => '9'],
                ['material_id' => $this->materials['button']->id, 'quantity' => '9'],
                // Listed twice: the unique index rejects it after the two lines above were written.
                ['material_id' => $this->materials['label']->id, 'quantity' => '9'],
            ],
        ], $this->user))->toThrow(QueryException::class);

        expect(storedLines($this->product))->toBe($before)
            ->and(Product::query()->find($this->product->id)->name)->toBe('Denim Jacket')
            ->and(Product::query()->find($this->product->id)->updated_by)->toBe($this->creator->id);
    });

    it('does not leave a stale composition on the model it returns', function () {
        $this->product->load('components');

        $updated = $this->update->handle($this->product, ['components' => []], $this->user);

        expect($updated->components)->toHaveCount(0);
    });
});

describe('SyncProductComponents', function () {
    it('is its own transaction when used alone', function () {
        $product = Product::factory()->create();

        expect(fn () => app(SyncProductComponents::class)->handle($product, [
            ['material_id' => $this->materials['denim']->id, 'quantity' => '1'],
            ['material_id' => $this->materials['denim']->id, 'quantity' => '2'],
        ]))->toThrow(QueryException::class);

        expect(storedLines($product))->toBe([]);
    });

    it('stores the same lines when it runs twice', function () {
        $product = Product::factory()->create();
        $sync = app(SyncProductComponents::class);
        $lines = workedExampleLines($this->materials);

        $sync->handle($product, $lines);
        $first = ProductComponent::query()->pluck('id')->all();
        $sync->handle($product, $lines);

        expect(ProductComponent::query()->pluck('id')->all())->toBe($first)
            ->and(storedLines($product))->toHaveCount(4);
    });
});

describe('the composition in the database', function () {
    it('rejects the same material twice on one product (unique index)', function () {
        $product = Product::factory()->create();
        $row = [
            'product_id' => $product->id, 'material_id' => $this->materials['denim']->id,
            'quantity' => '1.0000', 'waste_percent' => '0.00', 'position' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ];

        DB::table('product_components')->insert($row);

        expect(fn () => DB::transaction(fn () => DB::table('product_components')->insert($row)))->toThrow(QueryException::class);

        // The same material on another product is fine.
        DB::table('product_components')->insert([...$row, 'product_id' => Product::factory()->create()->id]);

        expect(ProductComponent::query()->count())->toBe(2);
    });
});

describe('DeleteProduct', function () {
    it('soft-deletes the product and keeps its composition', function () {
        $product = $this->create->handle(productData(['components' => workedExampleLines($this->materials)]), $this->user);

        app(DeleteProduct::class)->handle($product);

        assertSoftDeleted($product);
        expect(Product::query()->count())->toBe(0)
            ->and(Product::withTrashed()->count())->toBe(1)
            ->and(ProductComponent::query()->where('product_id', $product->id)->count())->toBe(4);
    });

    it('restores with its composition', function () {
        $product = $this->create->handle(productData(['components' => workedExampleLines($this->materials)]), $this->user);
        app(DeleteProduct::class)->handle($product);

        $product->restore();

        expect(Product::query()->with('components')->find($product->id)->components)->toHaveCount(4);
    });
});
