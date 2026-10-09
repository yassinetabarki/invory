<?php

use App\Enums\UnitDimension;
use App\Exceptions\ReferenceInUseException;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('casts', function () {
    it('casts a unit: dimension enum, factor as a 12-decimal string, booleans, dates, ints', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Mass, '0.45359237')->archived()->system()->create(['position' => 7]);
        $fresh = Unit::query()->findOrFail($unit->id);

        expect($fresh->dimension)->toBe(UnitDimension::Mass)
            ->and($fresh->factor_to_base)->toBe('0.453592370000')
            ->and($fresh->is_system)->toBeTrue()
            ->and($fresh->position)->toBe(7)
            ->and($fresh->archived_at)->toBeInstanceOf(CarbonInterface::class)
            ->and($fresh->isArchived())->toBeTrue();
    });

    it('casts the categories', function (string $model) {
        $row = $model::factory()->create(['position' => 3]);
        $fresh = $model::query()->findOrFail($row->id);

        expect($fresh->position)->toBe(3)
            ->and($fresh->archived_at)->toBeNull()
            ->and($fresh->isArchived())->toBeFalse()
            ->and($model::factory()->archived()->create()->isArchived())->toBeTrue();
    })->with(['material categories' => [MaterialCategory::class], 'product categories' => [ProductCategory::class]]);

    it('keeps a 12-decimal factor exact', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Length, '0.000000000001')->create();

        expect($unit->refresh()->factor_to_base)->toBe('0.000000000001');
    });
});

describe('scopes', function () {
    it('active hides archived rows', function (string $model) {
        $model::factory()->archived()->create(['code' => 'gone']);

        expect($model::query()->active()->where('code', 'gone')->exists())->toBeFalse()
            ->and($model::query()->where('code', 'gone')->exists())->toBeTrue();
    })->with(['units' => [Unit::class], 'material categories' => [MaterialCategory::class], 'product categories' => [ProductCategory::class]]);

    it('ordered sorts by position, then name, then id', function (string $model) {
        $model::query()->delete();

        $model::factory()->create(['code' => 'c', 'name' => 'Same', 'position' => 1]);
        $model::factory()->create(['code' => 'b', 'name' => 'Same', 'position' => 1]);
        $model::factory()->create(['code' => 'a', 'name' => 'Zed', 'position' => 0]);
        $model::factory()->create(['code' => 'd', 'name' => 'Alpha', 'position' => 1]);

        expect($model::query()->ordered()->pluck('code')->all())->toBe(['a', 'd', 'c', 'b']);
    })->with(['material categories' => [MaterialCategory::class], 'product categories' => [ProductCategory::class]]);
});

describe('relations', function () {
    it('counts the materials of a unit and of a category, soft-deleted ones only on demand', function () {
        $unit = Unit::factory()->create();
        $category = MaterialCategory::factory()->create();
        Material::factory()->count(2)->create(['unit' => $unit, 'category' => $category]);
        Material::factory()->create(['unit' => $unit, 'category' => $category])->delete();

        expect($unit->materials()->count())->toBe(2)
            ->and($unit->materials()->withTrashed()->count())->toBe(3)
            ->and($category->materials()->count())->toBe(2)
            ->and($category->materials()->withTrashed()->count())->toBe(3);
    });

    it('eager-loads the category and unit of every material', function () {
        Material::factory()->count(3)->create();

        $material = Material::query()->firstOrFail();

        expect($material->relationLoaded('category'))->toBeTrue()
            ->and($material->relationLoaded('unit'))->toBeTrue();
    });

    it('lets a narrow select skip the eager loads', function () {
        Material::factory()->create();

        $material = Material::query()->without(['category', 'unit'])->select(['id', 'unit_cost'])->firstOrFail();

        expect($material->relationLoaded('category'))->toBeFalse();
    });
});

describe('mass assignment', function () {
    it('only fills the declared columns, so an id or a timestamp can not be forced in', function (string $model, array $attributes) {
        expect(fn () => (new $model)->fill($attributes))->toThrow(MassAssignmentException::class);
    })->with([
        'unit' => [Unit::class, ['id' => 99]],
        'material category' => [MaterialCategory::class, ['created_at' => '2020-01-01']],
        'product category' => [ProductCategory::class, ['id' => 99]],
    ]);

    it('lets a material take category_id and unit_id but no longer category or unit', function () {
        expect(fn () => (new Material)->fill(['category' => 'fabric']))->toThrow(MassAssignmentException::class)
            ->and(fn () => (new Material)->fill(['unit' => 'm']))->toThrow(MassAssignmentException::class);

        $material = (new Material)->fill(['category_id' => 1, 'unit_id' => 2]);

        expect($material->category_id)->toBe(1)
            ->and($material->unit_id)->toBe(2);
    });
});

describe('labels', function () {
    it('labels a unit "name (code)"', function () {
        expect(Unit::factory()->make(['name' => 'Metre', 'code' => 'm'])->label())->toBe('Metre (m)');
    });
});

describe('ReferenceInUseException', function () {
    it('names the item, the count and the noun, singular or plural', function () {
        expect(ReferenceInUseException::inUse('Fabric', 1, 'material')->getMessage())->toBe("Fabric is used by 1 material, so it can't be deleted. Archive it instead.")
            ->and(ReferenceInUseException::inUse('Fabric', 2, 'material')->getMessage())->toBe("Fabric is used by 2 materials, so it can't be deleted. Archive it instead.")
            ->and(ReferenceInUseException::inUse('Bag', 4, 'product')->getMessage())->toBe("Bag is used by 4 products, so it can't be deleted. Archive it instead.")
            ->and(ReferenceInUseException::systemUnit('Metre (m)')->getMessage())->toBe("Metre (m) is a built-in unit, so it can't be deleted. Archive it instead.");
    });
});
