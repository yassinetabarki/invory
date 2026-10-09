<?php

use App\Enums\UnitDimension;
use App\Models\Material;
use App\Models\Unit;
use App\Models\User;
use App\Services\Reference\ReferenceVersion;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings > units (ADR-0008)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** The seeded unit with this code. */
function systemUnit(string $code): Unit
{
    return Unit::query()->where('code', $code)->firstOrFail();
}

/** A valid StoreUnitRequest payload (a custom length unit), with overrides. */
function validUnitPayload(array $overrides = []): array
{
    return ['code' => 'inch', 'name' => 'Inch', 'dimension' => 'length', 'factor_to_base' => '0.0254', 'position' => '20', ...$overrides];
}

describe('index', function () {
    it('lists every unit with its dimension, factor, status and usage', function () {
        Material::factory()->count(2)->create(['unit' => 'm']);
        Material::factory()->create(['unit' => 'm'])->delete();
        Unit::factory()->archived()->create(['code' => 'old', 'name' => 'Old unit', 'position' => 900]);

        actingAs($this->user)->get(route('settings.units.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/units')
                ->has('units', 14)
                ->where('dimensions', UnitDimension::options())
                ->has('units.0', fn (Assert $unit) => $unit
                    ->where('code', 'm')
                    ->where('name', 'Metre')
                    ->where('position', 0)
                    ->where('archived', false)
                    ->where('dimension', 'length')
                    ->where('dimension_label', 'Length')
                    ->where('factor_to_base', '1.000000000000')
                    ->where('is_system', true)
                    // Non-deleted materials only.
                    ->where('usage_count', 2)
                    ->whereType('id', 'integer'))
                ->where('units.13.code', 'old')
                ->where('units.13.archived', true)
                ->where('units.13.is_system', false));
    });

    it('sends exactly the contract keys, with a null factor for package units', function () {
        $units = collect(actingAs($this->user)->get(route('settings.units.index'))->inertiaProps('units'));

        expect(array_keys($units->first()))->toBe(['id', 'code', 'name', 'position', 'archived', 'dimension', 'dimension_label', 'factor_to_base', 'is_system', 'usage_count'])
            ->and($units->firstWhere('code', 'roll')['factor_to_base'])->toBeNull()
            ->and($units->firstWhere('code', 'roll')['dimension'])->toBe('package')
            ->and($units->firstWhere('code', 'pair')['factor_to_base'])->toBe('2.000000000000')
            // Decimal strings, never numbers.
            ->and($units->pluck('factor_to_base')->filter()->every(fn (mixed $factor): bool => is_string($factor)))->toBeTrue();
    });

    it('orders by position, then name', function () {
        Unit::factory()->create(['code' => 'b_two', 'name' => 'Bbb', 'position' => 5000]);
        Unit::factory()->create(['code' => 'a_two', 'name' => 'Aaa', 'position' => 5000]);
        Unit::factory()->create(['code' => 'first', 'name' => 'Zzz', 'position' => 0]);

        $codes = collect(actingAs($this->user)->get(route('settings.units.index'))->inertiaProps('units'))->pluck('code');

        // position 0: m, then first (name Zzz sorts after Metre); the two at 5000 come last, Aaa before Bbb.
        expect($codes->take(2)->all())->toBe(['m', 'first'])
            ->and($codes->slice(-2)->values()->all())->toBe(['a_two', 'b_two']);
    });
});

describe('create', function () {
    it('creates a custom unit, flashes a toast and bumps the reference version', function () {
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload())
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        $unit = Unit::query()->where('code', 'inch')->firstOrFail();

        expect($unit->name)->toBe('Inch')
            ->and($unit->dimension)->toBe(UnitDimension::Length)
            ->and($unit->factor_to_base)->toBe('0.025400000000')
            ->and($unit->is_system)->toBeFalse()
            ->and($unit->position)->toBe(20)
            ->and($unit->archived_at)->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
    });

    it('creates a package unit without a factor', function (mixed $factor) {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['code' => 'bale', 'name' => 'Bale', 'dimension' => 'package', 'factor_to_base' => $factor]))
            ->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', 'bale')->firstOrFail()->factor_to_base)->toBeNull();
    })->with(['null' => [null], 'empty' => ['']]);

    it('puts a unit without a position last', function () {
        $last = Unit::query()->max('position');

        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['position' => null]))->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', 'inch')->value('position'))->toBe($last + 1);
    });

    it('stores every dimension', function (string $dimension, ?string $factor) {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['dimension' => $dimension, 'factor_to_base' => $factor]))->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', 'inch')->firstOrFail()->dimension->value)->toBe($dimension);
    })->with([
        'length' => ['length', '1'], 'area' => ['area', '1'], 'mass' => ['mass', '1'],
        'volume' => ['volume', '1'], 'count' => ['count', '12'], 'package' => ['package', null],
    ]);

    it('keeps 12 decimals of the factor, exactly', function () {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['factor_to_base' => '0.000000000001']))->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', 'inch')->firstOrFail()->factor_to_base)->toBe('0.000000000001');
    });

    it('accepts the longest code, the largest factor and position 0', function () {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['code' => str_repeat('u', 16), 'factor_to_base' => '999999999999', 'position' => '0']))
            ->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', str_repeat('u', 16))->exists())->toBeTrue();
    });

    it('rejects invalid input', function (array $override, string $field) {
        Unit::factory()->create(['code' => 'taken', 'name' => 'Taken name']);
        Unit::factory()->archived()->create(['code' => 'old_code', 'name' => 'Old name']);
        $before = Unit::query()->count();

        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload($override))->assertSessionHasErrors($field);

        expect(Unit::query()->count())->toBe($before);
    })->with([
        'missing code' => [['code' => null], 'code'],
        'upper case code' => [['code' => 'Inch'], 'code'],
        'code with a dash' => [['code' => 'in-ch'], 'code'],
        'code with a space' => [['code' => 'in ch'], 'code'],
        'code too long' => [['code' => str_repeat('u', 17)], 'code'],
        'code taken' => [['code' => 'taken'], 'code'],
        'code of a system unit' => [['code' => 'm'], 'code'],
        'code of an archived unit, never reused' => [['code' => 'old_code'], 'code'],
        'missing name' => [['name' => null], 'name'],
        'name too long' => [['name' => str_repeat('n', 65)], 'name'],
        'name taken by an active unit' => [['name' => 'Taken name'], 'name'],
        'name of a system unit' => [['name' => 'Metre'], 'name'],
        'missing dimension' => [['dimension' => null], 'dimension'],
        'unknown dimension' => [['dimension' => 'weight'], 'dimension'],
        'missing factor' => [['factor_to_base' => null], 'factor_to_base'],
        'empty factor' => [['factor_to_base' => ''], 'factor_to_base'],
        'zero factor' => [['factor_to_base' => '0'], 'factor_to_base'],
        'zero factor with decimals' => [['factor_to_base' => '0.000000000000'], 'factor_to_base'],
        'negative factor' => [['factor_to_base' => '-1'], 'factor_to_base'],
        'factor with 13 decimals' => [['factor_to_base' => '0.0000000000001'], 'factor_to_base'],
        'factor too large' => [['factor_to_base' => '1000000000000'], 'factor_to_base'],
        'text factor' => [['factor_to_base' => 'abc'], 'factor_to_base'],
        'exponent factor' => [['factor_to_base' => '1e3'], 'factor_to_base'],
        'comma factor' => [['factor_to_base' => '0,5'], 'factor_to_base'],
        'a factor on a package unit' => [['dimension' => 'package', 'factor_to_base' => '1'], 'factor_to_base'],
        'a zero factor on a package unit' => [['dimension' => 'package', 'factor_to_base' => '0'], 'factor_to_base'],
        'negative position' => [['position' => '-1'], 'position'],
        'position too large' => [['position' => '32768'], 'position'],
        'decimal position' => [['position' => '1.5'], 'position'],
    ]);

    it('reports a missing dimension once, not as a second error on the factor', function () {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['dimension' => null, 'factor_to_base' => '1']))
            ->assertSessionHasErrors('dimension')
            ->assertSessionDoesntHaveErrors('factor_to_base');
    });

    it('lets a new unit reuse the name of an archived one', function () {
        Unit::factory()->archived()->create(['name' => 'Inch']);

        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload())->assertSessionHasNoErrors();

        expect(Unit::query()->where('name', 'Inch')->count())->toBe(2);
    });

    it('never creates a system unit, whatever the request says', function () {
        actingAs($this->user)->post(route('settings.units.store'), validUnitPayload(['is_system' => true]))->assertSessionHasNoErrors();

        expect(Unit::query()->where('code', 'inch')->firstOrFail()->is_system)->toBeFalse();
    });
});

describe('update', function () {
    it('renames and reorders a system unit, keeping its code, dimension and factor', function () {
        $unit = systemUnit('m');
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Meter', 'position' => '4'])
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        expect($unit->refresh()->name)->toBe('Meter')
            ->and($unit->position)->toBe(4)
            ->and($unit->code)->toBe('m')
            ->and($unit->dimension)->toBe(UnitDimension::Length)
            ->and($unit->factor_to_base)->toBe('1.000000000000')
            ->and($unit->is_system)->toBeTrue()
            ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
    });

    it('refuses to change the dimension or the factor of a system unit, even to the same value', function (string $field, string $value) {
        $unit = systemUnit('cm');

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Centimetre', $field => $value])->assertSessionHasErrors($field);

        expect($unit->refresh()->factor_to_base)->toBe('0.010000000000')
            ->and($unit->dimension)->toBe(UnitDimension::Length);
    })->with([
        'another dimension' => ['dimension', 'mass'],
        'the same dimension' => ['dimension', 'length'],
        'another factor' => ['factor_to_base', '0.5'],
        'the same factor' => ['factor_to_base', '0.01'],
    ]);

    it('refuses to change the code, even to the same value', function (string $code) {
        $unit = Unit::factory()->create(['code' => 'bolt', 'name' => 'Bolt']);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['code' => $code, 'name' => 'Bolt'])->assertSessionHasErrors('code');

        expect($unit->refresh()->code)->toBe('bolt');
    })->with(['another code' => ['other'], 'the same code' => ['bolt']]);

    it('changes the dimension and factor of a custom unit that no material uses', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Length, '0.5')->create(['name' => 'Fathom']);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Fathom', 'dimension' => 'mass', 'factor_to_base' => '1.8288'])->assertSessionHasNoErrors();

        expect($unit->refresh()->dimension)->toBe(UnitDimension::Mass)
            ->and($unit->factor_to_base)->toBe('1.828800000000');
    });

    it('changes only the factor when the dimension is not sent', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Length, '0.5')->create();

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => $unit->name, 'factor_to_base' => '0.75'])->assertSessionHasNoErrors();

        expect($unit->refresh()->factor_to_base)->toBe('0.750000000000')
            ->and($unit->dimension)->toBe(UnitDimension::Length);
    });

    it('leaves the dimension and factor alone when neither is sent', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Area, '0.3')->create(['name' => 'Before']);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'After'])->assertSessionHasNoErrors();

        expect($unit->refresh()->name)->toBe('After')
            ->and($unit->dimension)->toBe(UnitDimension::Area)
            ->and($unit->factor_to_base)->toBe('0.300000000000');
    });

    it('drops the factor when a custom unit becomes a package, and needs one when it stops being one', function () {
        $unit = Unit::factory()->dimension(UnitDimension::Count, '12')->create();

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => $unit->name, 'dimension' => 'package', 'factor_to_base' => null])->assertSessionHasNoErrors();
        expect($unit->refresh()->dimension)->toBe(UnitDimension::Package)
            ->and($unit->factor_to_base)->toBeNull();

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => $unit->name, 'dimension' => 'count'])->assertSessionHasErrors('factor_to_base');
        expect($unit->refresh()->dimension)->toBe(UnitDimension::Package);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => $unit->name, 'dimension' => 'count', 'factor_to_base' => '6'])->assertSessionHasNoErrors();
        expect($unit->refresh()->dimension)->toBe(UnitDimension::Count)
            ->and($unit->factor_to_base)->toBe('6.000000000000');
    });

    it('rejects invalid input', function (array $override, string $field) {
        Unit::factory()->create(['name' => 'Taken name']);
        $unit = Unit::factory()->dimension(UnitDimension::Length, '0.5')->create(['code' => 'mine', 'name' => 'Mine']);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Fine', 'dimension' => 'length', 'factor_to_base' => '0.5', ...$override])->assertSessionHasErrors($field);

        expect($unit->refresh()->name)->toBe('Mine');
    })->with([
        'missing name' => [['name' => null], 'name'],
        'name too long' => [['name' => str_repeat('n', 65)], 'name'],
        'name taken by an active unit' => [['name' => 'Taken name'], 'name'],
        'unknown dimension' => [['dimension' => 'weight'], 'dimension'],
        'empty dimension' => [['dimension' => null], 'dimension'],
        'zero factor' => [['factor_to_base' => '0'], 'factor_to_base'],
        'negative factor' => [['factor_to_base' => '-0.5'], 'factor_to_base'],
        'factor with 13 decimals' => [['factor_to_base' => '0.1234567890123'], 'factor_to_base'],
        'factor too large' => [['factor_to_base' => '1000000000000'], 'factor_to_base'],
        'text factor' => [['factor_to_base' => 'abc'], 'factor_to_base'],
        'empty factor' => [['factor_to_base' => ''], 'factor_to_base'],
        'a factor on a package unit' => [['dimension' => 'package', 'factor_to_base' => '1'], 'factor_to_base'],
        'negative position' => [['position' => '-1'], 'position'],
        'position too large' => [['position' => '32768'], 'position'],
    ]);

    it('keeps the same name without a conflict and lets an update take an archived unit\'s name', function () {
        Unit::factory()->archived()->create(['name' => 'Free name']);
        $unit = Unit::factory()->create(['name' => 'Same']);

        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Same'])->assertSessionHasNoErrors();
        actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => 'Free name'])->assertSessionHasNoErrors();

        expect($unit->refresh()->name)->toBe('Free name');
    });

    describe('while materials use the unit', function () {
        beforeEach(function () {
            $this->unit = Unit::factory()->dimension(UnitDimension::Length, '0.5')->create(['name' => 'Used']);
            Material::factory()->create(['unit' => $this->unit]);
        });

        it('refuses a new dimension', function () {
            actingAs($this->user)->put(route('settings.units.update', $this->unit->id), ['name' => 'Used', 'dimension' => 'mass', 'factor_to_base' => '0.5'])
                ->assertSessionHasErrors(['dimension' => "This unit is used by 1 material, so its dimension and factor can't change."]);

            expect($this->unit->refresh()->dimension)->toBe(UnitDimension::Length);
        });

        it('refuses a new factor, and says "materials" for several', function () {
            Material::factory()->create(['unit' => $this->unit]);

            actingAs($this->user)->put(route('settings.units.update', $this->unit->id), ['name' => 'Used', 'dimension' => 'length', 'factor_to_base' => '0.6'])
                ->assertSessionHasErrors(['dimension' => "This unit is used by 2 materials, so its dimension and factor can't change."]);

            expect($this->unit->refresh()->factor_to_base)->toBe('0.500000000000');
        });

        it('counts a soft-deleted material as a user', function () {
            $unit = Unit::factory()->dimension(UnitDimension::Length, '0.5')->create();
            Material::factory()->create(['unit' => $unit])->delete();

            actingAs($this->user)->put(route('settings.units.update', $unit->id), ['name' => $unit->name, 'factor_to_base' => '0.9'])->assertSessionHasErrors('dimension');
        });

        it('accepts the values it already has, in any spelling of the same number', function (string $factor) {
            actingAs($this->user)->put(route('settings.units.update', $this->unit->id), ['name' => 'Renamed', 'dimension' => 'length', 'factor_to_base' => $factor, 'position' => '3'])
                ->assertSessionHasNoErrors();

            expect($this->unit->refresh()->name)->toBe('Renamed')
                ->and($this->unit->position)->toBe(3)
                ->and($this->unit->factor_to_base)->toBe('0.500000000000');
        })->with(['0.5', '0.50', '0.500000000000', '.5']);

        it('lets the name and the position change', function () {
            actingAs($this->user)->put(route('settings.units.update', $this->unit->id), ['name' => 'Renamed', 'position' => '9'])->assertSessionHasNoErrors();

            expect($this->unit->refresh()->name)->toBe('Renamed');
        });
    });
});

describe('archive and restore', function () {
    it('archives and restores a unit at any time, even a system unit that materials use', function () {
        Material::factory()->create(['unit' => 'm']);
        $unit = systemUnit('m');
        $version = app(ReferenceVersion::class)->current();

        actingAs($this->user)->patch(route('settings.units.archive', $unit->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit archived.']);

        $archivedVersion = app(ReferenceVersion::class)->current();

        expect($unit->refresh()->archived_at)->not->toBeNull()
            ->and($archivedVersion)->not->toBe($version);

        actingAs($this->user)->patch(route('settings.units.restore', $unit->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit restored.']);

        expect($unit->refresh()->archived_at)->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->not->toBe($archivedVersion);
    });

    it('answers 404 for an unknown unit', function (string $method, string $action, array $payload) {
        actingAs($this->user)->{$method}(route("settings.units.{$action}", 999999), $payload)->assertNotFound();
    })->with([
        'update' => ['put', 'update', ['name' => 'X']],
        'destroy' => ['delete', 'destroy', []],
        'archive' => ['patch', 'archive', []],
        'restore' => ['patch', 'restore', []],
    ]);
});

describe('delete', function () {
    it('deletes a custom unit that nothing uses', function () {
        $unit = Unit::factory()->create();
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        expect(app(ReferenceVersion::class)->current())->not->toBe($before);
    });

    it('deletes an archived custom unit that nothing uses', function () {
        $unit = Unit::factory()->archived()->create();

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    });

    it('refuses a unit in use with an error toast naming the count', function () {
        $unit = Unit::factory()->create(['code' => 'bolt', 'name' => 'Bolt']);
        Material::factory()->count(2)->create(['unit' => $unit]);
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Bolt (bolt) is used by 2 materials, so it can't be deleted. Archive it instead."]);

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
        expect(app(ReferenceVersion::class)->current())->toBe($before);
    });

    it('says "material" for one user', function () {
        $unit = Unit::factory()->create(['code' => 'bolt', 'name' => 'Bolt']);
        Material::factory()->create(['unit' => $unit]);

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Bolt (bolt) is used by 1 material, so it can't be deleted. Archive it instead."]);
    });

    it('counts a soft-deleted material as a user, because the foreign key would refuse the delete', function () {
        $unit = Unit::factory()->create(['code' => 'bolt', 'name' => 'Bolt']);
        Material::factory()->create(['unit' => $unit])->delete();

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Bolt (bolt) is used by 1 material, so it can't be deleted. Archive it instead."]);

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    });

    it('never deletes a system unit, used or not, only archives it', function (string $code) {
        $unit = systemUnit($code);

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "{$unit->label()} is a built-in unit, so it can't be deleted. Archive it instead."]);

        $this->assertDatabaseHas('units', ['code' => $code]);
    })->with(['an unused system unit' => ['yd'], 'a used-by-default unit' => ['m'], 'a package unit' => ['roll']]);

    it('deletes a unit once its last material is gone for good', function () {
        $unit = Unit::factory()->create();
        $material = Material::factory()->create(['unit' => $unit]);

        $material->forceDelete();

        actingAs($this->user)->delete(route('settings.units.destroy', $unit->id))->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    });
});

it('keeps the version when a write is refused by validation', function () {
    $before = app(ReferenceVersion::class)->current();

    actingAs($this->user)->post(route('settings.units.store'), ['code' => 'Bad Code'])->assertSessionHasErrors(['code', 'name', 'dimension']);

    expect(app(ReferenceVersion::class)->current())->toBe($before);
});
