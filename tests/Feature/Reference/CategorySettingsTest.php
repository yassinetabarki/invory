<?php

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Reference\ReferenceVersion;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings > material categories and product categories (ADR-0008)
|--------------------------------------------------------------------------
|
| The two pages share one shape, so every test runs for both. The only difference is where "in use" comes from: materials
| are real rows; products are the preview fixture until S-04 (4 apparel, 1 footwear, 1 bag, 1 accessory).
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * @return array<string, array{array<string, mixed>}>
 */
dataset('categoryKinds', [
    'material categories' => [[
        'route' => 'settings.material-categories',
        'page' => 'settings/material-categories',
        'model' => MaterialCategory::class,
        'table' => 'material_categories',
        'defaults' => 12,
        'noun' => 'materials',
        // A category with 2 non-deleted materials and 1 soft-deleted one.
        'used' => function (): array {
            $category = MaterialCategory::factory()->create(['name' => 'Elastic']);
            Material::factory()->count(2)->create(['category' => $category]);
            Material::factory()->create(['category' => $category])->delete();

            return [$category, 2, 3];
        },
        // A category that only a soft-deleted material uses.
        'usedByTrashed' => function (): MaterialCategory {
            $category = MaterialCategory::factory()->create(['name' => 'Retired']);
            Material::factory()->create(['category' => $category])->delete();

            return $category;
        },
    ]],
    'product categories' => [[
        'route' => 'settings.product-categories',
        'page' => 'settings/product-categories',
        'model' => ProductCategory::class,
        'table' => 'product_categories',
        'defaults' => 6,
        'noun' => 'products',
        // MOCK (ADR-0006): the preview fixture has 4 apparel products.
        'used' => fn (): array => [ProductCategory::query()->where('code', 'apparel')->firstOrFail(), 4, 4],
        'usedByTrashed' => null,
    ]],
]);

it('lists the categories by position then name, with their usage', function (array $kind) {
    $kind['model']::factory()->create(['code' => 'zz_late', 'name' => 'Aaa late', 'position' => 500]);
    $kind['model']::factory()->create(['code' => 'aa_mid', 'name' => 'Bbb mid', 'position' => 200]);
    $kind['model']::factory()->create(['code' => 'zz_mid', 'name' => 'Aaa mid', 'position' => 200]);
    $kind['model']::factory()->archived()->create(['code' => 'gone', 'name' => 'Archived one', 'position' => 300]);

    actingAs($this->user)->get(route("{$kind['route']}.index"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($kind['page'])
            ->has('categories', $kind['defaults'] + 4)
            ->has('categories.0', fn (Assert $row) => $row->hasAll(['id', 'code', 'name', 'position', 'archived', 'usage_count'])->etc())
            ->where('categories', fn ($categories) => collect($categories)->pluck('code')->slice(-4)->values()->all() === ['zz_mid', 'aa_mid', 'gone', 'zz_late'])
            ->where('categories.0.position', 0)
            ->where('categories.0.archived', false));
})->with('categoryKinds');

it('sends exactly the contract keys and types on every row', function (array $kind) {
    $row = actingAs($this->user)->get(route("{$kind['route']}.index"))->inertiaProps('categories.0');

    expect(array_keys($row))->toBe(['id', 'code', 'name', 'position', 'archived', 'usage_count'])
        ->and($row['id'])->toBeInt()
        ->and($row['code'])->toBeString()
        ->and($row['name'])->toBeString()
        ->and($row['position'])->toBeInt()
        ->and($row['archived'])->toBeBool()
        ->and($row['usage_count'])->toBeInt();
})->with('categoryKinds');

it('counts the non-deleted users of each category', function (array $kind) {
    [$category, $count] = $kind['used']();

    $rows = collect(actingAs($this->user)->get(route("{$kind['route']}.index"))->inertiaProps('categories'));

    expect($rows->firstWhere('code', $category->code)['usage_count'])->toBe($count)
        ->and($rows->where('usage_count', '>', 0)->count())->toBeGreaterThan(0);
})->with('categoryKinds');

it('creates a category, flashes a toast and bumps the reference version', function (array $kind) {
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)
        ->post(route("{$kind['route']}.store"), ['code' => 'elastic', 'name' => 'Elastic', 'position' => '7'])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category created.']);

    $category = $kind['model']::query()->where('code', 'elastic')->firstOrFail();

    expect($category->name)->toBe('Elastic')
        ->and($category->position)->toBe(7)
        ->and($category->archived_at)->toBeNull()
        ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
})->with('categoryKinds');

it('puts a category without a position last', function (array $kind) {
    $last = $kind['model']::query()->max('position');

    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => 'newest', 'name' => 'Newest'])->assertRedirect();
    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => 'newer', 'name' => 'Newer', 'position' => ''])->assertRedirect();

    expect($kind['model']::query()->where('code', 'newest')->value('position'))->toBe($last + 1)
        ->and($kind['model']::query()->where('code', 'newer')->value('position'))->toBe($last + 2);
})->with('categoryKinds');

it('accepts position 0 and 32767 and the longest code', function (array $kind) {
    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => str_repeat('a', 32), 'name' => 'First', 'position' => '0'])->assertSessionHasNoErrors();
    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => 'b_9', 'name' => 'Last', 'position' => '32767'])->assertSessionHasNoErrors();

    expect($kind['model']::query()->where('code', str_repeat('a', 32))->value('position'))->toBe(0)
        ->and($kind['model']::query()->where('code', 'b_9')->value('position'))->toBe(32767);
})->with('categoryKinds');

it('rejects invalid category input on create', function (array $kind, array $override, string $field) {
    $kind['model']::factory()->create(['code' => 'taken', 'name' => 'Taken name']);
    $kind['model']::factory()->archived()->create(['code' => 'old_code', 'name' => 'Old name']);
    $before = $kind['model']::query()->count();

    actingAs($this->user)
        ->post(route("{$kind['route']}.store"), ['code' => 'fresh', 'name' => 'Fresh', 'position' => '1', ...$override])
        ->assertSessionHasErrors($field);

    expect($kind['model']::query()->count())->toBe($before);
})->with('categoryKinds')->with([
    'missing code' => [['code' => null], 'code'],
    'empty code' => [['code' => ''], 'code'],
    'upper case code' => [['code' => 'Fabric'], 'code'],
    'code with a dash' => [['code' => 'my-code'], 'code'],
    'code with a space' => [['code' => 'my code'], 'code'],
    'code with a dot' => [['code' => 'my.code'], 'code'],
    'code with accents' => [['code' => 'élastic'], 'code'],
    'code too long' => [['code' => str_repeat('a', 33)], 'code'],
    'code taken' => [['code' => 'taken'], 'code'],
    'code of an archived row, never reused' => [['code' => 'old_code'], 'code'],
    'code is an array' => [['code' => ['a']], 'code'],
    'missing name' => [['name' => null], 'name'],
    'empty name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('n', 65)], 'name'],
    'name taken by an active row' => [['name' => 'Taken name'], 'name'],
    'name is an array' => [['name' => ['a']], 'name'],
    'negative position' => [['position' => '-1'], 'position'],
    'position too large' => [['position' => '32768'], 'position'],
    'decimal position' => [['position' => '1.5'], 'position'],
    'text position' => [['position' => 'first'], 'position'],
]);

it('lets a new category reuse the name of an archived one', function (array $kind) {
    $kind['model']::factory()->archived()->create(['code' => 'old_code', 'name' => 'Reused']);

    actingAs($this->user)
        ->post(route("{$kind['route']}.store"), ['code' => 'new_code', 'name' => 'Reused'])
        ->assertSessionHasNoErrors();

    expect($kind['model']::query()->where('name', 'Reused')->count())->toBe(2);
})->with('categoryKinds');

it('renames and reorders a category but never changes its code', function (array $kind) {
    $category = $kind['model']::factory()->create(['code' => 'elastic', 'name' => 'Elastic', 'position' => 5]);
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)
        ->put(route("{$kind['route']}.update", $category->id), ['name' => 'Stretch', 'position' => '9'])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category updated.']);

    expect($category->refresh()->name)->toBe('Stretch')
        ->and($category->position)->toBe(9)
        ->and($category->code)->toBe('elastic')
        ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
})->with('categoryKinds');

it('keeps the position when an update sends none', function (array $kind) {
    $category = $kind['model']::factory()->create(['position' => 5]);

    actingAs($this->user)->put(route("{$kind['route']}.update", $category->id), ['name' => 'Renamed'])->assertSessionHasNoErrors();

    expect($category->refresh()->position)->toBe(5);
})->with('categoryKinds');

it('accepts an update that keeps the same name', function (array $kind) {
    $category = $kind['model']::factory()->create(['name' => 'Same']);

    actingAs($this->user)->put(route("{$kind['route']}.update", $category->id), ['name' => 'Same', 'position' => '3'])->assertSessionHasNoErrors();

    expect($category->refresh()->position)->toBe(3);
})->with('categoryKinds');

it('refuses to change the code on update, even to the same value', function (array $kind, string $code) {
    $category = $kind['model']::factory()->create(['code' => 'elastic', 'name' => 'Elastic']);

    actingAs($this->user)
        ->put(route("{$kind['route']}.update", $category->id), ['code' => $code, 'name' => 'Elastic'])
        ->assertSessionHasErrors('code');

    expect($category->refresh()->code)->toBe('elastic');
})->with('categoryKinds')->with(['another code' => ['other_code'], 'the same code' => ['elastic']]);

it('rejects invalid category input on update', function (array $kind, array $override, string $field) {
    $kind['model']::factory()->create(['code' => 'taken', 'name' => 'Taken name']);
    $category = $kind['model']::factory()->create(['code' => 'mine', 'name' => 'Mine']);

    actingAs($this->user)
        ->put(route("{$kind['route']}.update", $category->id), ['name' => 'Fine', ...$override])
        ->assertSessionHasErrors($field);

    expect($category->refresh()->name)->toBe('Mine');
})->with('categoryKinds')->with([
    'missing name' => [['name' => null], 'name'],
    'name too long' => [['name' => str_repeat('n', 65)], 'name'],
    'name taken by an active row' => [['name' => 'Taken name'], 'name'],
    'negative position' => [['position' => '-1'], 'position'],
    'position too large' => [['position' => '32768'], 'position'],
    'text position' => [['position' => 'first'], 'position'],
]);

it('lets an update take the name of an archived row', function (array $kind) {
    $kind['model']::factory()->archived()->create(['name' => 'Free name']);
    $category = $kind['model']::factory()->create();

    actingAs($this->user)->put(route("{$kind['route']}.update", $category->id), ['name' => 'Free name'])->assertSessionHasNoErrors();

    expect($category->refresh()->name)->toBe('Free name');
})->with('categoryKinds');

it('updates an archived category too', function (array $kind) {
    $category = $kind['model']::factory()->archived()->create(['name' => 'Old']);

    actingAs($this->user)->put(route("{$kind['route']}.update", $category->id), ['name' => 'Older'])->assertSessionHasNoErrors();

    expect($category->refresh()->name)->toBe('Older')
        ->and($category->archived_at)->not->toBeNull();
})->with('categoryKinds');

it('archives and restores a category at any time, even when it is in use', function (array $kind) {
    [$category] = $kind['used']();
    $version = app(ReferenceVersion::class)->current();

    actingAs($this->user)
        ->patch(route("{$kind['route']}.archive", $category->id))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category archived.']);

    $archivedVersion = app(ReferenceVersion::class)->current();

    expect($category->refresh()->archived_at)->not->toBeNull()
        ->and($archivedVersion)->not->toBe($version);

    actingAs($this->user)
        ->patch(route("{$kind['route']}.restore", $category->id))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category restored.']);

    expect($category->refresh()->archived_at)->toBeNull()
        ->and(app(ReferenceVersion::class)->current())->not->toBe($archivedVersion);
})->with('categoryKinds');

it('answers 404 for an unknown category on every write', function (array $kind, string $method, string $action, array $payload) {
    actingAs($this->user)->{$method}(route("{$kind['route']}.{$action}", 999999), $payload)->assertNotFound();
})->with('categoryKinds')->with([
    'update' => ['put', 'update', ['name' => 'X']],
    'destroy' => ['delete', 'destroy', []],
    'archive' => ['patch', 'archive', []],
    'restore' => ['patch', 'restore', []],
]);

it('deletes an unused category', function (array $kind) {
    $category = $kind['model']::factory()->create(['name' => 'Unused']);
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)
        ->delete(route("{$kind['route']}.destroy", $category->id))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

    $this->assertDatabaseMissing($kind['table'], ['id' => $category->id]);
    expect(app(ReferenceVersion::class)->current())->not->toBe($before);
})->with('categoryKinds');

it('refuses to delete a category in use, with an error toast naming the count', function (array $kind) {
    [$category, , $total] = $kind['used']();
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)
        ->delete(route("{$kind['route']}.destroy", $category->id))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "{$category->name} is used by {$total} {$kind['noun']}, so it can't be deleted. Archive it instead.",
        ]);

    $this->assertDatabaseHas($kind['table'], ['id' => $category->id]);
    // Nothing changed, so nothing is resent.
    expect(app(ReferenceVersion::class)->current())->toBe($before);
})->with('categoryKinds');

it('counts a soft-deleted material as a user, because the foreign key would refuse the delete', function () {
    $category = MaterialCategory::factory()->create(['name' => 'Retired']);
    Material::factory()->create(['category' => $category])->delete();

    actingAs($this->user)
        ->delete(route('settings.material-categories.destroy', $category->id))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Retired is used by 1 material, so it can't be deleted. Archive it instead."]);

    $this->assertDatabaseHas('material_categories', ['id' => $category->id]);
});

it('says "material" for one user and "materials" for several', function () {
    $one = MaterialCategory::factory()->create(['name' => 'One']);
    $two = MaterialCategory::factory()->create(['name' => 'Two']);
    Material::factory()->create(['category' => $one]);
    Material::factory()->count(2)->create(['category' => $two]);

    actingAs($this->user)->delete(route('settings.material-categories.destroy', $one->id))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "One is used by 1 material, so it can't be deleted. Archive it instead."]);
    actingAs($this->user)->delete(route('settings.material-categories.destroy', $two->id))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Two is used by 2 materials, so it can't be deleted. Archive it instead."]);
});

it('deletes a material category once its last material is gone for good', function () {
    $category = MaterialCategory::factory()->create();
    $material = Material::factory()->create(['category' => $category]);

    $material->forceDelete();

    actingAs($this->user)->delete(route('settings.material-categories.destroy', $category->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

    $this->assertDatabaseMissing('material_categories', ['id' => $category->id]);
});

it('deletes the product categories no preview product uses, and refuses the ones they use', function () {
    // MOCK (ADR-0006): the fixture has apparel (4), footwear, bag and accessory (1 each); jewelry and other are free.
    foreach (['apparel' => 4, 'footwear' => 1, 'bag' => 1, 'accessory' => 1] as $code => $count) {
        $category = ProductCategory::query()->where('code', $code)->firstOrFail();

        actingAs($this->user)->delete(route('settings.product-categories.destroy', $category->id))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => ucfirst($code)." is used by {$count} ".($count === 1 ? 'product' : 'products').", so it can't be deleted. Archive it instead.",
            ]);

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    foreach (['jewelry', 'other'] as $code) {
        $category = ProductCategory::query()->where('code', $code)->firstOrFail();

        actingAs($this->user)->delete(route('settings.product-categories.destroy', $category->id))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        $this->assertDatabaseMissing('product_categories', ['code' => $code]);
    }
});

it('shows the usage of the product categories from the preview fixture', function () {
    $rows = collect(actingAs($this->user)->get(route('settings.product-categories.index'))->inertiaProps('categories'));

    expect($rows->pluck('usage_count', 'code')->all())->toBe([
        'apparel' => 4, 'footwear' => 1, 'bag' => 1, 'accessory' => 1, 'jewelry' => 0, 'other' => 0,
    ]);
});

it('keeps the version when a write is refused by validation', function (array $kind) {
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => 'Bad Code', 'name' => ''])->assertSessionHasErrors(['code', 'name']);

    expect(app(ReferenceVersion::class)->current())->toBe($before);
})->with('categoryKinds');

it('never touches the other kind of category', function (array $kind) {
    $other = $kind['model'] === MaterialCategory::class ? ProductCategory::class : MaterialCategory::class;
    $before = $other::query()->orderBy('id')->get()->toArray();

    actingAs($this->user)->post(route("{$kind['route']}.store"), ['code' => 'elastic', 'name' => 'Elastic'])->assertRedirect();

    expect($other::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with('categoryKinds');
