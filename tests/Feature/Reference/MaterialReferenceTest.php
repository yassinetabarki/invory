<?php

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Materials and products on the reference tables (ADR-0008, S-21)
|--------------------------------------------------------------------------
|
| Forms and filters keep sending CODES; the validation reads the tables, hides archived rows from the option props (except the
| value the record already has), and a material's unit is locked while a product's composition uses it (decision Q7).
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
});

describe('the option lists moved to the shared reference prop (S-22)', function () {
    it('sends no categories or units prop on any materials or products page', function (string $route, array $parameters, array $absent) {
        $this->preview = Material::factory()->create();
        $parameters = array_map(fn ($value) => match ($value) {
            '{material}' => $this->preview,
            '{product}' => pid(1),
            default => $value,
        }, $parameters);

        actingAs($this->user)->get(route($route, $parameters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missingAll($absent));
    })->with([
        'materials index' => ['materials.index', [], ['categories', 'units']],
        'materials create' => ['materials.create', [], ['categories', 'units']],
        'materials edit' => ['materials.edit', ['{material}'], ['categories', 'units']],
        'products index' => ['products.index', [], ['categories', 'units']],
        'products create' => ['products.create', [], ['categories', 'units']],
        'products edit' => ['products.edit', ['{product}'], ['categories', 'units']],
    ]);

    it('still sends the free enum lists that stay per page', function () {
        actingAs($this->user)->get(route('products.create'))
            ->assertInertia(fn (Assert $page) => $page->has('statuses', 3));
    });

    it('lets the pages read the lists from the shared prop: archived rows come with the flag, by position then name', function () {
        MaterialCategory::factory()->create(['code' => 'elastic', 'name' => 'Elastic', 'position' => 5]);
        Unit::query()->where('code', 'cm')->update(['archived_at' => now()]);

        $response = actingAs($this->user)->get(route('materials.create'));

        $categories = collect($response->inertiaProps('reference.material_categories'));
        $units = collect($response->inertiaProps('reference.units'))->keyBy('code');

        expect($categories->pluck('code')->all())->toBe(['fabric', 'lining', 'interlining', 'leather', 'thread', 'elastic', 'trim', 'fastener', 'label', 'sole', 'hardware', 'packaging', 'other'])
            ->and($units->get('m')['name'])->toBe('Metre')
            ->and($units->get('cm')['archived'])->toBeTrue()
            ->and($units->get('m')['archived'])->toBeFalse();
    });
});

describe('the Material resource', function () {
    it('shows the category name and "name (code)" for the unit, and follows a rename', function () {
        $material = Material::factory()->create(['category' => 'sole', 'unit' => 'pair']);

        $props = actingAs($this->user)->get(route('materials.show', $material))->inertiaProps('material');

        expect($props)->toMatchArray(['category' => 'sole', 'category_label' => 'Sole', 'unit' => 'pair', 'unit_label' => 'Pair (pair)']);

        MaterialCategory::query()->where('code', 'sole')->update(['name' => 'Outsole']);
        Unit::query()->where('code', 'pair')->update(['name' => 'Couple']);

        $props = actingAs($this->user)->get(route('materials.show', $material))->inertiaProps('material');

        expect($props)->toMatchArray(['category' => 'sole', 'category_label' => 'Outsole', 'unit' => 'pair', 'unit_label' => 'Couple (pair)']);
    });

    it('still shows an archived category and unit on the materials that use them', function () {
        $material = Material::factory()->create(['category' => 'thread', 'unit' => 'cone']);
        MaterialCategory::query()->where('code', 'thread')->update(['archived_at' => now()]);
        Unit::query()->where('code', 'cone')->update(['archived_at' => now()]);

        $row = collect(actingAs($this->user)->get(route('materials.index', ['search' => $material->sku]))->inertiaProps('materials.data'))->first();

        expect($row)->toMatchArray(['category' => 'thread', 'category_label' => 'Thread', 'unit' => 'cone', 'unit_label' => 'Cone (cone)']);
    });
});

describe('creating and updating a material', function () {
    it('rejects an archived category or unit, and an unknown one, on create', function (string $field, string $code, bool $archive) {
        if ($archive) {
            ($field === 'category' ? MaterialCategory::class : Unit::class)::query()->where('code', $code)->update(['archived_at' => now()]);
        }

        actingAs($this->user)->post(route('materials.store'), validMaterialPayload([$field => $code]))->assertSessionHasErrors($field);

        expect(Material::query()->where('sku', 'FAB-DEN-001')->exists())->toBeFalse();
    })->with([
        'archived category' => ['category', 'fabric', true],
        'archived unit' => ['unit', 'm', true],
        'unknown category' => ['category', 'elastic', false],
        'unknown unit' => ['unit', 'inch', false],
        'upper case code' => ['category', 'Fabric', false],
        'code of the name' => ['unit', 'Metre', false],
    ]);

    it('accepts a custom category and unit on create, resolved to their ids', function () {
        $category = MaterialCategory::factory()->create(['code' => 'elastic', 'name' => 'Elastic']);
        $unit = Unit::factory()->create(['code' => 'inch', 'name' => 'Inch']);

        actingAs($this->user)->post(route('materials.store'), validMaterialPayload(['category' => 'elastic', 'unit' => 'inch']))->assertSessionHasNoErrors();

        $material = Material::query()->where('sku', 'FAB-DEN-001')->firstOrFail();

        expect($material->category_id)->toBe($category->id)
            ->and($material->unit_id)->toBe($unit->id)
            ->and($material->category->code)->toBe('elastic')
            ->and($material->unit->code)->toBe('inch');
    });

    it('rejects a category or unit sent as an array', function (string $field) {
        actingAs($this->user)->post(route('materials.store'), validMaterialPayload([$field => ['fabric']]))->assertSessionHasErrors($field);
    })->with(['category', 'unit']);

    it('does not accept an id where the code goes', function (string $field) {
        actingAs($this->user)->post(route('materials.store'), validMaterialPayload([$field => (string) Unit::query()->value('id')]))->assertSessionHasErrors($field);
    })->with(['category', 'unit']);

    it('keeps an archived category or unit on update when the material already has it, and can switch to an active one', function () {
        $material = Material::factory()->create(['sku' => 'KEEP-1', 'category' => 'thread', 'unit' => 'cone']);
        MaterialCategory::query()->where('code', 'thread')->update(['archived_at' => now()]);
        Unit::query()->where('code', 'cone')->update(['archived_at' => now()]);

        // The same archived values: fine (the unit lock does not apply, the unit does not change).
        actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload(['sku' => 'KEEP-1', 'category' => 'thread', 'unit' => 'cone', 'name' => 'Renamed']))
            ->assertSessionHasNoErrors();

        expect($material->refresh()->name)->toBe('Renamed')
            ->and($material->category->code)->toBe('thread')
            ->and($material->unit->code)->toBe('cone');

        // Leaving the archived values for active ones: fine.
        actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload(['sku' => 'KEEP-1', 'category' => 'fabric', 'unit' => 'm']))->assertSessionHasNoErrors();

        expect($material->refresh()->category->code)->toBe('fabric')
            ->and($material->unit->code)->toBe('m');
    });

    it('does not let an update move a material to another archived category or unit', function (string $field, string $code) {
        $material = Material::factory()->create(['sku' => 'KEEP-1', 'category' => 'fabric', 'unit' => 'm']);
        MaterialCategory::query()->where('code', 'lining')->update(['archived_at' => now()]);
        Unit::query()->where('code', 'cm')->update(['archived_at' => now()]);

        actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload(['sku' => 'KEEP-1', $field => $code]))->assertSessionHasErrors($field);

        expect($material->refresh()->category->code)->toBe('fabric')
            ->and($material->unit->code)->toBe('m');
    })->with([
        'archived category' => ['category', 'lining'],
        'archived unit' => ['unit', 'cm'],
    ]);

    it('exposes the new category and unit right after an update', function () {
        $material = Material::factory()->create(['sku' => 'KEEP-1', 'category' => 'fabric', 'unit' => 'm']);

        actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload(['sku' => 'KEEP-1', 'category' => 'leather', 'unit' => 'sqft']))->assertSessionHasNoErrors();

        $props = actingAs($this->user)->get(route('materials.show', $material))->inertiaProps('material');

        expect($props)->toMatchArray(['category' => 'leather', 'category_label' => 'Leather', 'unit' => 'sqft', 'unit_label' => 'Square foot (sqft)']);
    });

    it('filters the list by a category code, archived or not, and 422s on an unknown one', function () {
        Material::factory()->create(['name' => 'Zq thread', 'category' => 'thread']);
        Material::factory()->create(['name' => 'Zq fabric', 'category' => 'fabric']);
        MaterialCategory::query()->where('code', 'thread')->update(['archived_at' => now()]);

        $names = fn (array $query): array => collect(actingAs($this->user)->get(route('materials.index', ['search' => 'zq', ...$query]))->inertiaProps('materials.data'))->pluck('name')->all();

        expect($names(['category' => 'thread']))->toBe(['Zq thread'])
            ->and($names(['category' => 'fabric']))->toBe(['Zq fabric'])
            ->and($names([]))->toHaveCount(2);

        actingAs($this->user)->get(route('materials.index', ['category' => 'elastic']))->assertSessionHasErrors('category');
    });

    it('filters by a custom category too', function () {
        MaterialCategory::factory()->create(['code' => 'elastic', 'name' => 'Elastic']);
        Material::factory()->create(['name' => 'Waistband', 'category' => 'elastic']);
        Material::factory()->create(['name' => 'Denim', 'category' => 'fabric']);

        $rows = actingAs($this->user)->get(route('materials.index', ['category' => 'elastic']))->inertiaProps('materials.data');

        expect(collect($rows)->pluck('name')->all())->toBe(['Waistband'])
            ->and($rows[0]['category_label'])->toBe('Elastic');
    });
});

describe('the product forms', function () {
    it('rejects an archived or unknown product category on create', function (string $code, bool $archive) {
        if ($archive) {
            ProductCategory::query()->where('code', $code)->update(['archived_at' => now()]);
        }

        actingAs($this->user)->post(route('products.store'), validProductPayload(['category' => $code]))->assertSessionHasErrors('category');
    })->with(['archived' => ['apparel', true], 'unknown' => ['hats', false]]);

    it('accepts a custom product category on create', function () {
        ProductCategory::factory()->create(['code' => 'hats', 'name' => 'Hats']);

        actingAs($this->user)->post(route('products.store'), validProductPayload(['category' => 'hats']))->assertSessionHasNoErrors();
    });

    it('keeps the current archived category valid on update, and refuses another archived one', function () {
        ProductCategory::query()->whereIn('code', ['apparel', 'bag'])->update(['archived_at' => now()]);

        // The Denim Jacket is in apparel.
        $jacket = pid(1);
        actingAs($this->user)->put(route('products.update', $jacket), validProductPayload(['sku' => 'PRD-001', 'category' => 'apparel']))->assertSessionHasNoErrors();
        actingAs($this->user)->put(route('products.update', $jacket), validProductPayload(['sku' => 'PRD-001', 'category' => 'bag']))->assertSessionHasErrors('category');
    });

    it('filters the products list by an archived category code and 422s on an unknown one', function () {
        ProductCategory::query()->where('code', 'footwear')->update(['archived_at' => now()]);

        actingAs($this->user)->get(route('products.index', ['category' => 'footwear']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('products.data.0.category', 'footwear')->where('products.data.0.category_label', 'Footwear'));

        actingAs($this->user)->get(route('products.index', ['category' => 'hats']))->assertSessionHasErrors('category');
    });

    it('labels the products from the product_categories table, read once', function () {
        ProductCategory::query()->where('code', 'apparel')->update(['name' => 'Clothing']);

        $queries = queryLog(fn () => $response = actingAs($this->user)->get(route('products.index')));
        $response = actingAs($this->user)->get(route('products.index'));

        $labels = collect($response->inertiaProps('products.data'))->pluck('category_label', 'category')->all();

        expect($labels['apparel'])->toBe('Clothing')
            ->and($labels['footwear'])->toBe('Footwear')
            // The names of the products (once, not per product) and the shared reference lists of a full load. The option list's
            // own query is gone since S-22: 3 reads became 2.
            ->and(queriesMatching($queries, 'from "product_categories"'))->toHaveCount(2)
            ->and(actingAs($this->user)->get(route('products.show', pid(1)))->inertiaProps('product.category_label'))->toBe('Clothing');
    });

    it('can no longer lose the category of a product: the foreign key refuses to delete it', function () {
        // `products.category_id` restricts: a category some product points at can't be removed from the table (the settings page
        // archives it instead), so a product always has its category row.
        expect(fn () => ProductCategory::query()->where('code', 'bag')->delete())->toThrow(QueryException::class);
    });
});

describe('the unit lock while a product uses the material (decision Q7)', function () {
    /** [sku, how many products use it] for the first material used by exactly one product, and the first used by several. */
    $usage = function (): array {
        $counts = Material::query()->withUsedInCount()->orderBy('id')->get()->pluck('used_in_count', 'sku')->filter(fn (int $count): bool => $count > 0);

        return [
            [$counts->filter(fn (int $count): bool => $count === 1)->keys()->first(), 1],
            [$counts->filter(fn (int $count): bool => $count > 1)->keys()->first(), $counts->filter(fn (int $count): bool => $count > 1)->first()],
        ];
    };

    /** The payload that updates a material to another unit, everything else unchanged. */
    $payload = fn (Material $material, array $overrides = []): array => validMaterialPayload([
        'sku' => $material->sku,
        'name' => $material->name,
        'category' => $material->category->code,
        'unit' => $material->unit->code,
        'unit_cost' => $material->unit_cost,
        'quantity_on_hand' => $material->quantity_on_hand,
        'reorder_level' => $material->reorder_level,
        ...$overrides,
    ]);

    it('refuses a unit change on a material that compositions use, naming how many', function (int $which) use ($usage, $payload) {
        [$sku, $count] = $usage()[$which];
        $material = demoMaterial($sku);
        $other = $material->unit->code === 'kg' ? 'g' : 'kg';

        $noun = $count === 1 ? 'composition' : 'compositions';

        actingAs($this->user)->put(route('materials.update', $material), $payload($material, ['unit' => $other]))
            ->assertSessionHasErrors(['unit' => "This material is used in {$count} product {$noun}, so its unit can't change."]);

        expect($material->refresh()->unit->code)->not->toBe($other);
    })->with(['a material in one product' => [0], 'a material in several products' => [1]]);

    it('refuses even when other fields change in the same request', function () use ($usage, $payload) {
        [$sku] = $usage()[0];
        $material = demoMaterial($sku);
        $name = $material->name;

        actingAs($this->user)->put(route('materials.update', $material), $payload($material, ['unit' => 'kg', 'name' => 'Renamed', 'unit_cost' => '99']))
            ->assertSessionHasErrors('unit');

        // Nothing was saved, not even the other fields.
        expect($material->refresh()->name)->toBe($name);
    });

    it('lets an unused material change its unit', function () use ($payload) {
        $material = Material::factory()->create(['sku' => 'ZZZ-OTHER', 'name' => 'Zzz other', 'unit' => 'm']);

        actingAs($this->user)->put(route('materials.update', $material), $payload($material, ['unit' => 'kg']))->assertSessionHasNoErrors();

        expect($material->refresh()->unit->code)->toBe('kg')
            ->and($material->unit->label())->toBe('Kilogram (kg)');
    });

    it('lets a used material keep its unit while other fields change', function () use ($usage, $payload) {
        [$sku] = $usage()[1];
        $material = demoMaterial($sku);

        actingAs($this->user)->put(route('materials.update', $material), $payload($material, ['name' => 'Renamed', 'unit_cost' => '1.25', 'category' => 'other', 'supplier_name' => 'New supplier']))
            ->assertSessionHasNoErrors();

        expect($material->refresh()->name)->toBe('Renamed')
            ->and($material->unit_cost)->toBe('1.2500')
            ->and($material->category->code)->toBe('other')
            ->and($material->unit->code)->toBe($payload($material)['unit']);
    });

    it('does not run the lock on a unit that failed validation, so only the validation error shows', function () use ($usage, $payload) {
        [$sku] = $usage()[0];
        $material = demoMaterial($sku);

        $response = actingAs($this->user)->put(route('materials.update', $material), $payload($material, ['unit' => 'bananas']));

        $response->assertSessionHasErrors('unit');
        expect(session('errors')->get('unit'))->toHaveCount(1)
            ->and(session('errors')->first('unit'))->not->toContain('product composition');
    });

    it('does not lock the create form', function () {
        actingAs($this->user)->post(route('materials.store'), validMaterialPayload(['sku' => 'NEW-1', 'unit' => 'kg']))->assertSessionHasNoErrors();
    });

    it('locks the unit of a material the demo products use', function () {
        // The denim is in the compositions of the Denim Jacket and the Jeans.
        $material = demoMaterial('MAT-DEN-012');

        actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload([
            'sku' => $material->sku, 'name' => $material->name, 'unit' => 'cm',
        ]))->assertSessionHasErrors(['unit' => "This material is used in 2 product compositions, so its unit can't change."]);
    });

    it('allows the change once only soft-deleted products use the material, and locks it again on restore', function () {
        $material = demoMaterial('MAT-SOL-042');
        $sneaker = demoProduct('PRD-SNK-003');
        $update = fn (string $unit) => actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload([
            'sku' => $material->sku, 'name' => $material->name, 'category' => 'sole', 'unit' => $unit, 'reorder_level' => '40',
        ]));

        // The cupsole is only in the composition of the Sneaker.
        $update('kg')->assertSessionHasErrors(['unit' => "This material is used in 1 product composition, so its unit can't change."]);

        $sneaker->delete();
        $update('kg')->assertSessionHasNoErrors();
        expect($material->refresh()->unit->code)->toBe('kg');

        // Restore the Sneaker: its line is back in use, so the unit is locked again.
        $sneaker->restore();
        $update('g')->assertSessionHasErrors('unit');
    });

    it('locks the unit as soon as a product created through the form uses the material', function () {
        $material = Material::factory()->create(['sku' => 'ZZZ-NEW', 'name' => 'Zzz new', 'unit' => 'm']);
        $payload = fn (string $unit): array => validMaterialPayload(['sku' => 'ZZZ-NEW', 'name' => 'Zzz new', 'unit' => $unit, 'category' => $material->category->code]);

        actingAs($this->user)->put(route('materials.update', $material), $payload('kg'))->assertSessionHasNoErrors();

        actingAs($this->user)->post(route('products.store'), validProductPayload(['components' => [
            ['material_id' => $material->id, 'quantity' => '2', 'waste_percent' => '0'],
        ]]))->assertSessionHasNoErrors();

        actingAs($this->user)->put(route('materials.update', $material), $payload('g'))
            ->assertSessionHasErrors(['unit' => "This material is used in 1 product composition, so its unit can't change."]);
    });
});

describe('the material factory', function () {
    it('takes category and unit as codes, models or ids, and resolves them to ids', function () {
        $category = MaterialCategory::query()->where('code', 'leather')->firstOrFail();
        $unit = Unit::query()->where('code', 'sqft')->firstOrFail();

        $byCode = Material::factory()->create(['category' => 'thread', 'unit' => 'cone']);
        $byModel = Material::factory()->create(['category' => $category, 'unit' => $unit]);
        $byId = Material::factory()->create(['category' => $category->id, 'unit' => $unit->id]);
        $byColumn = Material::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

        expect($byCode->category->code)->toBe('thread')
            ->and($byCode->unit->code)->toBe('cone')
            ->and($byModel->category_id)->toBe($category->id)
            ->and($byModel->unit_id)->toBe($unit->id)
            ->and($byId->category_id)->toBe($category->id)
            ->and($byColumn->unit_id)->toBe($unit->id);
    });

    it('makes a realistic default material with a default category and unit', function () {
        $material = Material::factory()->create();

        expect($material->category)->toBeInstanceOf(MaterialCategory::class)
            ->and($material->unit)->toBeInstanceOf(Unit::class)
            ->and(MaterialCategory::query()->where('code', $material->category->code)->exists())->toBeTrue();
    });

    it('fails with a clear message for a code that has no row', function () {
        expect(fn () => Material::factory()->create(['unit' => 'bananas']))->toThrow(InvalidArgumentException::class, 'No unit with code [bananas].')
            ->and(fn () => Material::factory()->create(['category' => 'bananas']))->toThrow(InvalidArgumentException::class, 'No category with code [bananas].');
    });

    it('builds an unsaved material without touching its relations', function () {
        $material = Material::factory()->make(['category' => 'thread']);

        expect($material->category_id)->toBe(MaterialCategory::query()->where('code', 'thread')->value('id'));
    });
});

it('refuses a request from a guest or an unverified user as before', function () {
    $material = Material::factory()->create();

    $this->put(route('materials.update', $material), validMaterialPayload())->assertRedirect(route('login'));
    actingAs(User::factory()->unverified()->create())->put(route('materials.update', $material), validMaterialPayload())->assertRedirect(route('verification.notice'));
});
