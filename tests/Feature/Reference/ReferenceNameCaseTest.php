<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Names of units and categories are unique among the active rows, ignoring case (F-32, ADR-0008)
|--------------------------------------------------------------------------
|
| Create, update and restore validate with `lower(name) = lower(?)` in SQL, and the database keeps a partial unique index on
| `lower(name) WHERE archived_at IS NULL`, so both fold the same way. Every test uses ASCII names: PostgreSQL's `lower()` also folds
| non-ASCII letters (with a Unicode-aware locale) and SQLite's does not, so a non-ASCII name would behave differently on the two engines.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * @return array<string, array{array<string, mixed>}>
 */
dataset('nameKinds', [
    'units' => [[
        'model' => Unit::class,
        'table' => 'units',
        'route' => 'settings.units',
        'noun' => 'unit',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name, 'dimension' => 'length', 'factor_to_base' => '0.0254'],
    ]],
    'material categories' => [[
        'model' => MaterialCategory::class,
        'table' => 'material_categories',
        'route' => 'settings.material-categories',
        'noun' => 'material category',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name],
    ]],
    'product categories' => [[
        'model' => ProductCategory::class,
        'table' => 'product_categories',
        'route' => 'settings.product-categories',
        'noun' => 'product category',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name],
    ]],
]);

describe('create', function () {
    it('is refused when an active row has the same name in another case', function (array $kind, string $existing, string $new) {
        $kind['model']::factory()->create(['name' => $existing]);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']($new))
            ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

        expect($kind['model']::query()->where('code', 'new_one')->exists())->toBeFalse();
    })->with('nameKinds')->with([
        'lower over title' => ['Elastic', 'elastic'],
        'title over lower' => ['elastic', 'Elastic'],
        'upper over title' => ['Elastic', 'ELASTIC'],
        'mixed' => ['Elastic Band', 'eLaStIc bAnD'],
    ]);

    it('is still refused for the exact same name', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic'))->assertSessionHasErrors('name');
    })->with('nameKinds');

    it('is allowed when the only namesake is archived', function (array $kind) {
        $kind['model']::factory()->archived()->create(['name' => 'elastic']);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic'))
            ->assertSessionHasNoErrors();

        expect($kind['model']::query()->where('code', 'new_one')->value('name'))->toBe('Elastic');
    })->with('nameKinds');

    it('is allowed for a different name', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);

        actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic band'))->assertSessionHasNoErrors();
    })->with('nameKinds');
});

describe('update', function () {
    it('is refused when another active row has the name in another case', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $other = $kind['model']::factory()->create(['name' => 'Plastic']);

        actingAs($this->user)->put(route("{$kind['route']}.update", $other->id), ['name' => 'elastic'])
            ->assertSessionHasErrors(['name' => 'The name has already been taken.']);

        expect($other->refresh()->name)->toBe('Plastic');
    })->with('nameKinds');

    it('lets a row change the case of its own name', function (array $kind) {
        $row = $kind['model']::factory()->create(['name' => 'Elastic']);

        actingAs($this->user)->put(route("{$kind['route']}.update", $row->id), ['name' => 'ELASTIC'])
            ->assertSessionHasNoErrors();

        expect($row->refresh()->name)->toBe('ELASTIC');
    })->with('nameKinds');

    it('is allowed when the only namesake is archived', function (array $kind) {
        $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $row = $kind['model']::factory()->create(['name' => 'Plastic']);

        actingAs($this->user)->put(route("{$kind['route']}.update", $row->id), ['name' => 'ELASTIC'])
            ->assertSessionHasNoErrors();

        expect($row->refresh()->name)->toBe('ELASTIC');
    })->with('nameKinds');
});

describe('restore', function () {
    it('is refused when an active row has the name in another case, and names the active row as stored', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'ELASTIC']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertSessionHasErrors(['name' => "An active {$kind['noun']} is already named \"Elastic\". Rename one of them first."]);

        expect($archived->refresh()->archived_at)->not->toBeNull();
    })->with('nameKinds');

    it('is allowed when the only namesake is archived', function (array $kind) {
        $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'ELASTIC']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))->assertSessionHasNoErrors();

        expect($archived->refresh()->archived_at)->toBeNull();
    })->with('nameKinds');

    it('does not refuse an active row, which only ignores itself', function (array $kind) {
        $active = $kind['model']::factory()->create(['name' => 'Elastic']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $active->id))->assertSessionHasNoErrors();
    })->with('nameKinds');
});

describe('the database guard', function () {
    // A failed statement aborts the surrounding PostgreSQL transaction, so each one runs in its own savepoint (DB::transaction).
    it('rejects an active row whose name matches an active one in another case', function (array $kind, string $new) {
        DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'Elastic']));

        expect(fn () => DB::transaction(fn () => DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => $new]))))
            ->toThrow(QueryException::class);

        expect(DB::table($kind['table'])->whereRaw('lower(name) = ?', ['elastic'])->count())->toBe(1);
    })->with('nameKinds')->with(['same case' => ['Elastic'], 'lower' => ['elastic'], 'upper' => ['ELASTIC']]);

    it('accepts an archived namesake, before or after the active row', function (array $kind) {
        DB::table($kind['table'])->insert($kind['model']::factory()->archived()->raw(['name' => 'Elastic']));
        DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'ELASTIC']));
        DB::table($kind['table'])->insert($kind['model']::factory()->archived()->raw(['name' => 'elastic']));

        expect(DB::table($kind['table'])->whereRaw('lower(name) = ?', ['elastic'])->count())->toBe(3);
    })->with('nameKinds');

    it('rejects an update that makes two active rows clash, and a restore that does', function (array $kind) {
        DB::table($kind['table'])->insert($kind['model']::factory()->raw(['name' => 'Elastic']));
        $plastic = DB::table($kind['table'])->insertGetId($kind['model']::factory()->raw(['name' => 'Plastic']));
        $archived = DB::table($kind['table'])->insertGetId($kind['model']::factory()->archived()->raw(['name' => 'ELASTIC']));

        expect(fn () => DB::transaction(fn () => DB::table($kind['table'])->where('id', $plastic)->update(['name' => 'elastic'])))
            ->toThrow(QueryException::class)
            ->and(fn () => DB::transaction(fn () => DB::table($kind['table'])->where('id', $archived)->update(['archived_at' => null])))
            ->toThrow(QueryException::class);
    })->with('nameKinds');

    it('guards the seeded defaults too', function () {
        expect(fn () => DB::transaction(fn () => DB::table('units')->insert(Unit::factory()->raw(['name' => 'METRE']))))
            ->toThrow(QueryException::class)
            ->and(fn () => DB::transaction(fn () => DB::table('material_categories')->insert(MaterialCategory::factory()->raw(['name' => 'fabric']))))
            ->toThrow(QueryException::class)
            ->and(fn () => DB::transaction(fn () => DB::table('product_categories')->insert(ProductCategory::factory()->raw(['name' => 'BAG']))))
            ->toThrow(QueryException::class);
    });
});
