<?php

use App\Actions\Reference\CreateMaterialCategory;
use App\Actions\Reference\CreateProductCategory;
use App\Actions\Reference\CreateUnit;
use App\Actions\Reference\RestoreMaterialCategory;
use App\Actions\Reference\RestoreProductCategory;
use App\Actions\Reference\RestoreUnit;
use App\Actions\Reference\UpdateMaterialCategory;
use App\Actions\Reference\UpdateProductCategory;
use App\Actions\Reference\UpdateUnit;
use App\Http\Requests\Reference\RestoreMaterialCategoryRequest;
use App\Http\Requests\Reference\RestoreProductCategoryRequest;
use App\Http\Requests\Reference\RestoreUnitRequest;
use App\Http\Requests\Reference\StoreMaterialCategoryRequest;
use App\Http\Requests\Reference\StoreProductCategoryRequest;
use App\Http\Requests\Reference\StoreUnitRequest;
use App\Http\Requests\Reference\UpdateMaterialCategoryRequest;
use App\Http\Requests\Reference\UpdateProductCategoryRequest;
use App\Http\Requests\Reference\UpdateUnitRequest;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Two people save the same name at the same moment (F-33, ADR-0008)
|--------------------------------------------------------------------------
|
| Both pass validation, so the second write meets the partial unique index `<table>_active_name_unique`. The Actions turn that into
| the validation error the rule would have given, instead of a 500. The race is simulated by bypassing the rule: either the Action is called
| with data that clashes, or a clashing active row is inserted right AFTER the request has validated (an `afterResolving` hook runs after
| the form request's own validation) and before the Action writes. Every test uses ASCII names, as in ReferenceNameCaseTest.
|
| The conversion works through DB::transaction(), so on PostgreSQL the surrounding test transaction survives the failed statement.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** Counts the rollbacks (a savepoint included): that is how a test knows the database itself refused a write, not the validation. */
function trackRollbacks(): object
{
    $counter = new class
    {
        public int $count = 0;
    };

    Event::listen(TransactionRolledBack::class, function () use ($counter): void {
        $counter->count++;
    });

    return $counter;
}

dataset('raceKinds', [
    'units' => [[
        'model' => Unit::class,
        'table' => 'units',
        'route' => 'settings.units',
        'noun' => 'unit',
        'storeRequest' => StoreUnitRequest::class,
        'updateRequest' => UpdateUnitRequest::class,
        'restoreRequest' => RestoreUnitRequest::class,
        'create' => CreateUnit::class,
        'update' => UpdateUnit::class,
        'restore' => RestoreUnit::class,
        'store' => fn (string $name, string $code = 'new_one'): array => ['code' => $code, 'name' => $name, 'dimension' => 'length', 'factor_to_base' => '0.0254'],
    ]],
    'material categories' => [[
        'model' => MaterialCategory::class,
        'table' => 'material_categories',
        'route' => 'settings.material-categories',
        'noun' => 'material category',
        'storeRequest' => StoreMaterialCategoryRequest::class,
        'updateRequest' => UpdateMaterialCategoryRequest::class,
        'restoreRequest' => RestoreMaterialCategoryRequest::class,
        'create' => CreateMaterialCategory::class,
        'update' => UpdateMaterialCategory::class,
        'restore' => RestoreMaterialCategory::class,
        'store' => fn (string $name, string $code = 'new_one'): array => ['code' => $code, 'name' => $name],
    ]],
    'product categories' => [[
        'model' => ProductCategory::class,
        'table' => 'product_categories',
        'route' => 'settings.product-categories',
        'noun' => 'product category',
        'storeRequest' => StoreProductCategoryRequest::class,
        'updateRequest' => UpdateProductCategoryRequest::class,
        'restoreRequest' => RestoreProductCategoryRequest::class,
        'create' => CreateProductCategory::class,
        'update' => UpdateProductCategory::class,
        'restore' => RestoreProductCategory::class,
        'store' => fn (string $name, string $code = 'new_one'): array => ['code' => $code, 'name' => $name],
    ]],
]);

/** Inserts an active row that clashes, once the request has passed validation and before the Action writes. */
function winTheRace(string $requestClass, string $table, string $model, array $attributes): void
{
    app()->afterResolving($requestClass, function () use ($table, $model, $attributes): void {
        DB::table($table)->insert($model::factory()->raw($attributes));
    });
}

function matchingRows(string $table, string $lowerName): int
{
    return DB::table($table)->whereRaw('lower(name) = ?', [$lowerName])->count();
}

/** A unique violation as each engine words it, wrapped the way Laravel wraps the PDO exception. */
function uniqueViolation(string $pdoMessage): UniqueConstraintViolationException
{
    return new UniqueConstraintViolationException(
        'testing',
        'insert into "units" ("code", "name") values (?, ?)',
        ['u_1', 'Elastic'],
        new PDOException($pdoMessage),
    );
}

describe('over HTTP', function () {
    it('answers a name error, not a 500, when the name was taken after validation (create)', function (array $kind) {
        $version = app(ReferenceVersion::class)->current();
        $rollbacks = trackRollbacks();
        winTheRace($kind['storeRequest'], $kind['table'], $kind['model'], ['name' => 'ELASTIC']);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic'))
            ->assertRedirect()
            ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

        expect($rollbacks->count)->toBe(1)
            ->and($kind['model']::query()->where('code', 'new_one')->exists())->toBeFalse()
            ->and(matchingRows($kind['table'], 'elastic'))->toBe(1)
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('answers a name error when the name was taken after validation (update)', function (array $kind) {
        $row = $kind['model']::factory()->create(['name' => 'Plastic']);
        $version = app(ReferenceVersion::class)->current();
        $rollbacks = trackRollbacks();
        winTheRace($kind['updateRequest'], $kind['table'], $kind['model'], ['name' => 'ELASTIC']);

        actingAs($this->user)->put(route("{$kind['route']}.update", $row->id), ['name' => 'Elastic'])
            ->assertRedirect()
            ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

        expect($rollbacks->count)->toBe(1)
            ->and($row->refresh()->name)->toBe('Plastic')
            ->and(matchingRows($kind['table'], 'elastic'))->toBe(1)
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('answers the restore error naming the active row when the name was taken after validation (restore)', function (array $kind) {
        $archived = $kind['model']::factory()->archived()->create(['name' => 'ELASTIC']);
        $version = app(ReferenceVersion::class)->current();
        $rollbacks = trackRollbacks();
        winTheRace($kind['restoreRequest'], $kind['table'], $kind['model'], ['name' => 'Elastic']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertRedirect()
            ->assertSessionHasErrors(['name' => "An active {$kind['noun']} is already named \"Elastic\". Rename one of them first."]);

        expect($rollbacks->count)->toBe(1)
            ->and($archived->refresh()->archived_at)->not->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('answers a code error when the code was taken after validation (create)', function (array $kind) {
        $version = app(ReferenceVersion::class)->current();
        $rollbacks = trackRollbacks();
        winTheRace($kind['storeRequest'], $kind['table'], $kind['model'], ['code' => 'new_one', 'name' => 'Another name']);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic'))
            ->assertRedirect()
            ->assertSessionHasErrors(['code' => 'The code has already been taken.']);

        expect($rollbacks->count)->toBe(1)
            ->and($kind['model']::query()->where('code', 'new_one')->count())->toBe(1)
            ->and($kind['model']::query()->where('name', 'Elastic')->exists())->toBeFalse()
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('lets an unrelated unique violation through', function (array $kind) {
        $existing = $kind['model']::factory()->create();
        // The new row asks for the id of an existing one: a primary-key violation, neither the name index nor the code.
        $kind['model']::creating(fn ($row) => $row->id = $existing->id);

        $this->withoutExceptionHandling();

        expect(fn () => actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic')))
            ->toThrow(UniqueConstraintViolationException::class);
    })->with('raceKinds');
});

describe('the Actions', function () {
    it('create: turns a name clash into a validation error on name and writes nothing', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $version = app(ReferenceVersion::class)->current();
        $before = $kind['model']::query()->count();

        try {
            app($kind['create'])->handle($kind['store']('ELASTIC'));
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toBe(['name' => ['The name has already been taken.']]);
        }

        expect($kind['model']::query()->count())->toBe($before)
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('create: turns a code clash into a validation error on code', function (array $kind) {
        $kind['model']::factory()->archived()->create(['code' => 'new_one', 'name' => 'Old']);
        $version = app(ReferenceVersion::class)->current();

        try {
            app($kind['create'])->handle($kind['store']('Brand new'));
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toBe(['code' => ['The code has already been taken.']]);
        }

        expect($kind['model']::query()->where('name', 'Brand new')->exists())->toBeFalse()
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('update: turns a name clash into a validation error on name and keeps the old name', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $row = $kind['model']::factory()->create(['name' => 'Plastic']);
        $version = app(ReferenceVersion::class)->current();

        try {
            app($kind['update'])->handle($row, ['name' => 'eLASTIC']);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toBe(['name' => ['The name has already been taken.']]);
        }

        expect($kind['model']::query()->find($row->id)?->name)->toBe('Plastic')
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('update: still renames freely when nothing clashes', function (array $kind) {
        $row = $kind['model']::factory()->create(['name' => 'Plastic']);

        app($kind['update'])->handle($row, ['name' => 'Elastic']);

        expect($kind['model']::query()->find($row->id)?->name)->toBe('Elastic');
    })->with('raceKinds');

    it('restore: turns a name clash into the restore message naming the active row, and the row stays archived', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'ELASTIC']);
        $version = app(ReferenceVersion::class)->current();

        try {
            app($kind['restore'])->handle($archived);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toBe(['name' => ["An active {$kind['noun']} is already named \"Elastic\". Rename one of them first."]]);
        }

        expect($kind['model']::query()->find($archived->id)?->archived_at)->not->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');

    it('restore: names the clashing row as stored, however many rows clash in other cases', function (array $kind) {
        $kind['model']::factory()->archived()->create(['name' => 'elastic']);
        $kind['model']::factory()->create(['name' => 'ElAsTiC']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'ELASTIC']);

        expect(fn () => app($kind['restore'])->handle($archived))
            ->toThrow(ValidationException::class, "An active {$kind['noun']} is already named \"ElAsTiC\".");
    })->with('raceKinds');

    it('restore: a free name restores and bumps the version', function (array $kind) {
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $version = app(ReferenceVersion::class)->current();

        app($kind['restore'])->handle($archived);

        expect($kind['model']::query()->find($archived->id)?->archived_at)->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->not->toBe($version);
    })->with('raceKinds');

    it('lets an unrelated unique violation through, without a version bump', function (array $kind) {
        $existing = $kind['model']::factory()->create();
        $version = app(ReferenceVersion::class)->current();
        $kind['model']::creating(fn ($row) => $row->id = $existing->id);

        expect(fn () => app($kind['create'])->handle($kind['store']('Elastic')))->toThrow(UniqueConstraintViolationException::class)
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('raceKinds');
});

describe('the guard', function () {
    it('recognises the name index and the code constraint in the wording of PostgreSQL and SQLite', function (string $pdoMessage, string $field) {
        $violation = uniqueViolation($pdoMessage);

        try {
            ReferenceNameGuard::saving('units', fn () => throw $violation);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect(array_keys($e->errors()))->toBe([$field]);
        }
    })->with([
        'pgsql name' => ['SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "units_active_name_unique"', 'name'],
        'sqlite name' => ["SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: index 'units_active_name_unique'", 'name'],
        'pgsql code' => ['SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "units_code_unique"', 'code'],
        'sqlite code' => ['SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: units.code', 'code'],
    ]);

    it('rethrows a unique violation of another constraint, or of another table\'s index, untouched', function (string $pdoMessage) {
        $violation = uniqueViolation($pdoMessage);

        expect(fn () => ReferenceNameGuard::saving('units', fn () => throw $violation))->toThrow(fn (UniqueConstraintViolationException $e) => expect($e)->toBe($violation));
    })->with([
        'pgsql pkey' => ['SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "units_pkey"'],
        'sqlite id' => ['SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: units.id'],
        'another table' => ['SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "material_categories_active_name_unique"'],
        'sku' => ["SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: index 'materials_sku_unique'"],
    ]);

    it('does not look at the SQL text that Laravel appends to the message', function () {
        // The statement mentions the name index, the engine's own message is about the primary key: not ours.
        $violation = new UniqueConstraintViolationException(
            'testing',
            'insert into "units" -- units_active_name_unique',
            [],
            new PDOException('UNIQUE constraint failed: units.id'),
        );

        expect(fn () => ReferenceNameGuard::saving('units', fn () => throw $violation))->toThrow(UniqueConstraintViolationException::class);
    });

    it('answers a restore violation with the generic name error when no active row holds the name any more', function () {
        $archived = Unit::factory()->archived()->create(['name' => 'Elastic']);
        $violation = uniqueViolation("UNIQUE constraint failed: index 'units_active_name_unique'");

        try {
            ReferenceNameGuard::restoring('units', 'unit', $archived, fn () => throw $violation);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toBe(['name' => ['The name has already been taken.']]);
        }
    });

    it('still rethrows a restore violation of another constraint untouched', function () {
        $archived = Unit::factory()->archived()->create(['name' => 'Elastic']);
        $violation = uniqueViolation('UNIQUE constraint failed: units.id');

        expect(fn () => ReferenceNameGuard::restoring('units', 'unit', $archived, fn () => throw $violation))
            ->toThrow(fn (UniqueConstraintViolationException $e) => expect($e)->toBe($violation));
    });

    it('rolls the failed statement back to a savepoint, so the surrounding transaction keeps working', function (array $kind) {
        DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'Elastic']));
        $levelBefore = DB::transactionLevel();

        expect(fn () => ReferenceNameGuard::saving($kind['table'], fn () => DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'ELASTIC']))))
            ->toThrow(ValidationException::class)
            ->and(DB::transactionLevel())->toBe($levelBefore);

        // On PostgreSQL an aborted transaction refuses every statement: this one proves it is alive.
        expect(matchingRows($kind['table'], 'elastic'))->toBe(1);
    })->with('raceKinds');

    it('is a QueryException subclass thrown by the engine for the guarded clash', function (array $kind) {
        // Documents what the guard catches: the engine really throws Laravel's unique violation for the name index.
        DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'Elastic']));

        try {
            DB::transaction(fn () => DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'ELASTIC'])));
            $this->fail('The index should have refused the row.');
        } catch (QueryException $e) {
            expect($e)->toBeInstanceOf(UniqueConstraintViolationException::class)
                ->and($e->getPrevious()?->getMessage())->toContain("{$kind['table']}_active_name_unique");
        }
    })->with('raceKinds');
});
