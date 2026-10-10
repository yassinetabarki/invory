<?php

use App\Actions\Materials\CreateMaterial;
use App\Actions\Materials\RestockMaterial;
use App\Actions\Materials\UpdateMaterial;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| The stock ledger written by the material Actions (S-16-BE, ADR-0010)
|--------------------------------------------------------------------------
|
| Create logs `created`, update logs `correction` / `price_change`, restock logs `restock` (+ `price_change`). Rules: the
| `invory-costing-domain` skill, "Restock and price history". Time is frozen at 2026-10-08.
*/

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-08 12:00:00'));
    $this->actor = User::factory()->create(['name' => 'Amel']);
});

/**
 * The ledger of a material, in entry order, as plain arrays of the columns that matter.
 *
 * @return list<array<string, mixed>>
 */
function ledgerOf(Material $material): array
{
    return StockMovement::query()->where('material_id', $material->id)->orderBy('id')->get()
        ->map(fn (StockMovement $m): array => [
            'type' => $m->type->value,
            'occurred_on' => $m->occurred_on->toDateString(),
            'quantity' => $m->quantity,
            'quantity_before' => $m->quantity_before,
            'quantity_after' => $m->quantity_after,
            'unit_price' => $m->unit_price,
            'unit_cost_before' => $m->unit_cost_before,
            'unit_cost_after' => $m->unit_cost_after,
            'reference' => $m->reference,
            'note' => $m->note,
            'user_id' => $m->user_id,
        ])->all();
}

/** A material with a known stock and cost, and an empty ledger (the factory logs nothing). */
function ledgerMaterial(string $stock = '250.0000', string $cost = '8.5000'): Material
{
    return Material::factory()->create(['unit_cost' => $cost, 'quantity_on_hand' => $stock]);
}

describe('CreateMaterial', function () {
    it('logs the opening entry with the stock and the unit cost the material starts with', function () {
        $material = app(CreateMaterial::class)->handle(validMaterialPayload(['quantity_on_hand' => '250', 'unit_cost' => '8.5']), $this->actor);

        expect(ledgerOf($material))->toBe([[
            'type' => 'created',
            'occurred_on' => '2026-10-08',
            'quantity' => '250.0000',
            'quantity_before' => null,
            'quantity_after' => '250.0000',
            'unit_price' => '8.5000',
            'unit_cost_before' => null,
            'unit_cost_after' => null,
            'reference' => null,
            'note' => null,
            'user_id' => $this->actor->id,
        ]]);
    });

    it('logs an opening entry for a material that starts without stock', function () {
        $material = app(CreateMaterial::class)->handle(validMaterialPayload(['quantity_on_hand' => '0', 'unit_cost' => '0']), $this->actor);

        expect(ledgerOf($material))->toHaveCount(1)
            ->and(ledgerOf($material)[0])->toMatchArray(['type' => 'created', 'quantity' => '0.0000', 'quantity_after' => '0.0000', 'unit_price' => '0.0000']);
    });

    it('writes the material and its entry together or not at all', function () {
        // The same SKU twice: the second save breaks the unique index, so no second ledger entry may exist either.
        app(CreateMaterial::class)->handle(validMaterialPayload(), $this->actor);

        expect(fn () => app(CreateMaterial::class)->handle(validMaterialPayload(), $this->actor))->toThrow(QueryException::class)
            ->and(Material::query()->count())->toBe(1)
            ->and(StockMovement::query()->count())->toBe(1);
    });

    it('logs one entry through the create form', function () {
        $this->actingAs($this->actor)->post(route('materials.store'), validMaterialPayload())->assertSessionHasNoErrors();

        $material = Material::query()->where('sku', 'FAB-DEN-001')->firstOrFail();

        expect(ledgerOf($material))->toHaveCount(1)
            ->and(ledgerOf($material)[0])->toMatchArray(['type' => 'created', 'quantity' => '250.0000', 'unit_price' => '8.5000', 'user_id' => $this->actor->id]);
    });
});

describe('UpdateMaterial', function () {
    it('logs a correction with the stock before and after when the quantity changes, with no reason', function () {
        $material = ledgerMaterial('250.0000');

        app(UpdateMaterial::class)->handle($material, validMaterialPayload(['unit_cost' => '8.5', 'quantity_on_hand' => '240.5']), $this->actor);

        expect(ledgerOf($material))->toBe([[
            'type' => 'correction',
            'occurred_on' => '2026-10-08',
            'quantity' => null,
            'quantity_before' => '250.0000',
            'quantity_after' => '240.5000',
            'unit_price' => null,
            'unit_cost_before' => null,
            'unit_cost_after' => null,
            'reference' => null,
            'note' => null,
            'user_id' => $this->actor->id,
        ]])->and($material->fresh()->quantity_on_hand)->toBe('240.5000');
    });

    it('logs a price change, with the stock at that moment, when the unit cost changes', function () {
        $material = ledgerMaterial('250.0000', '8.5000');

        app(UpdateMaterial::class)->handle($material, validMaterialPayload(['unit_cost' => '9.25', 'quantity_on_hand' => '250']), $this->actor);

        expect(ledgerOf($material))->toBe([[
            'type' => 'price_change',
            'occurred_on' => '2026-10-08',
            'quantity' => null,
            'quantity_before' => null,
            'quantity_after' => '250.0000',
            'unit_price' => null,
            'unit_cost_before' => '8.5000',
            'unit_cost_after' => '9.2500',
            'reference' => null,
            'note' => null,
            'user_id' => $this->actor->id,
        ]]);
    });

    it('logs both entries, the correction first, when the stock and the cost change together', function () {
        $material = ledgerMaterial('250.0000', '8.5000');

        app(UpdateMaterial::class)->handle($material, validMaterialPayload(['unit_cost' => '7', 'quantity_on_hand' => '300']), $this->actor);

        expect(array_column(ledgerOf($material), 'type'))->toBe(['correction', 'price_change'])
            ->and(ledgerOf($material)[1])->toMatchArray(['quantity_after' => '300.0000', 'unit_cost_before' => '8.5000', 'unit_cost_after' => '7.0000']);
    });

    it('logs nothing when neither the stock nor the cost changes', function (array $override) {
        $material = ledgerMaterial('250.0000', '8.5000');

        app(UpdateMaterial::class)->handle($material, validMaterialPayload(['unit_cost' => '8.5', 'quantity_on_hand' => '250', ...$override]), $this->actor);

        expect(ledgerOf($material))->toBe([]);
    })->with([
        'only the name' => [['name' => 'Renamed']],
        'only the reorder level' => [['reorder_level' => '10']],
        'same values written with other decimals' => [['unit_cost' => '8.50', 'quantity_on_hand' => '250.0']],
    ]);

    it('compares with the stock under the lock, not with the copy the request loaded', function () {
        $material = ledgerMaterial('250.0000');
        $loaded = Material::query()->findOrFail($material->id); // what the route binding read ...

        DB::table('materials')->where('id', $material->id)->update(['quantity_on_hand' => '400.0000']); // ... before a receipt landed

        // The form still shows 250 and saves it: the stock really goes 400 -> 250, and the ledger says so.
        app(UpdateMaterial::class)->handle($loaded, validMaterialPayload(['unit_cost' => '8.5', 'quantity_on_hand' => '250']), $this->actor);

        expect($material->fresh()->quantity_on_hand)->toBe('250.0000')
            ->and(ledgerOf($material))->toHaveCount(1)
            ->and(ledgerOf($material)[0])->toMatchArray(['type' => 'correction', 'quantity_before' => '400.0000', 'quantity_after' => '250.0000']);
    });

    it('logs one correction through the edit form', function () {
        $material = ledgerMaterial('250.0000');

        $this->actingAs($this->actor)->put(route('materials.update', $material), validMaterialPayload(['unit_cost' => '8.5', 'quantity_on_hand' => '100']))
            ->assertSessionHasNoErrors();

        expect(array_column(ledgerOf($material), 'type'))->toBe(['correction']);
    });
});

describe('RestockMaterial', function () {
    it('adds the quantity, takes the price paid as the new unit cost and logs a restock and a price change', function () {
        $material = ledgerMaterial('900.0000', '0.3200');

        $result = app(RestockMaterial::class)->handle($material, [
            'quantity' => '500', 'unit_price' => '0.35', 'received_on' => '2026-10-02', 'reference' => 'PO-2291', 'update_unit_cost' => true,
        ], $this->actor);

        expect($result->quantity_on_hand)->toBe('1400.0000')
            ->and($result->unit_cost)->toBe('0.3500')
            ->and($material->fresh()->quantity_on_hand)->toBe('1400.0000')
            ->and($material->fresh()->unit_cost)->toBe('0.3500')
            ->and($material->fresh()->updated_by)->toBe($this->actor->id)
            ->and(ledgerOf($material))->toBe([
                [
                    'type' => 'restock',
                    'occurred_on' => '2026-10-02',
                    'quantity' => '500.0000',
                    'quantity_before' => null,
                    'quantity_after' => '1400.0000',
                    'unit_price' => '0.3500',
                    'unit_cost_before' => null,
                    'unit_cost_after' => null,
                    'reference' => 'PO-2291',
                    'note' => null,
                    'user_id' => $this->actor->id,
                ],
                [
                    'type' => 'price_change',
                    'occurred_on' => '2026-10-02',
                    'quantity' => null,
                    'quantity_before' => null,
                    'quantity_after' => '1400.0000',
                    'unit_price' => null,
                    'unit_cost_before' => '0.3200',
                    'unit_cost_after' => '0.3500',
                    'reference' => null,
                    'note' => null,
                    'user_id' => $this->actor->id,
                ],
            ]);
    });

    it('updates the unit cost when update_unit_cost is omitted', function () {
        $material = ledgerMaterial('10.0000', '1.0000');

        app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08'], $this->actor);

        expect($material->fresh()->unit_cost)->toBe('2.0000')
            ->and(array_column(ledgerOf($material), 'type'))->toBe(['restock', 'price_change']);
    });

    it('keeps the unit cost and logs only the restock when update_unit_cost is false', function () {
        $material = ledgerMaterial('10.0000', '1.0000');

        app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08', 'update_unit_cost' => false], $this->actor);

        expect($material->fresh()->quantity_on_hand)->toBe('15.0000')
            ->and($material->fresh()->unit_cost)->toBe('1.0000')
            ->and(array_column(ledgerOf($material), 'type'))->toBe(['restock'])
            ->and(ledgerOf($material)[0])->toMatchArray(['quantity' => '5.0000', 'quantity_after' => '15.0000', 'unit_price' => '2.0000']);
    });

    it('logs no price change when the price paid equals the unit cost', function (string $paid) {
        $material = ledgerMaterial('10.0000', '1.2500');

        app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => $paid, 'received_on' => '2026-10-08', 'update_unit_cost' => true], $this->actor);

        expect(array_column(ledgerOf($material), 'type'))->toBe(['restock'])
            ->and($material->fresh()->unit_cost)->toBe('1.2500');
    })->with(['the same digits' => '1.25', 'more digits' => '1.2500']);

    it('takes the latest price paid, not an average', function () {
        $material = ledgerMaterial('100.0000', '10.0000');

        app(RestockMaterial::class)->handle($material, ['quantity' => '100', 'unit_price' => '20', 'received_on' => '2026-10-08'], $this->actor);

        // A weighted average would be 15.
        expect($material->fresh()->unit_cost)->toBe('20.0000');
    });

    it('allows a free restock and lowers the cost to zero', function () {
        $material = ledgerMaterial('10.0000', '3.0000');

        app(RestockMaterial::class)->handle($material, ['quantity' => '1', 'unit_price' => '0', 'received_on' => '2026-10-08'], $this->actor);

        expect($material->fresh()->unit_cost)->toBe('0.0000')
            ->and(ledgerOf($material)[1])->toMatchArray(['type' => 'price_change', 'unit_cost_before' => '3.0000', 'unit_cost_after' => '0.0000']);
    });

    it('adds decimals exactly', function (string $stock, string $received, string $expected) {
        $material = ledgerMaterial($stock, '1.0000');

        app(RestockMaterial::class)->handle($material, ['quantity' => $received, 'unit_price' => '1', 'received_on' => '2026-10-08'], $this->actor);

        expect($material->fresh()->quantity_on_hand)->toBe($expected);
    })->with([
        '0.1 + 0.2' => ['0.1000', '0.2000', '0.3000'],
        'the smallest quantity' => ['0.0000', '0.0001', '0.0001'],
        'four decimals' => ['1234567.8901', '0.1099', '1234568.0000'],
        'up to the column limit' => ['9999999999.9998', '0.0001', '9999999999.9999'],
    ]);

    it('refuses a receipt that would overflow the stock column and changes nothing', function () {
        $material = ledgerMaterial('9999999999.9999', '1.0000');

        try {
            app(RestockMaterial::class)->handle($material, ['quantity' => '0.0001', 'unit_price' => '5', 'received_on' => '2026-10-08'], $this->actor);
            $this->fail('A ValidationException was expected.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('quantity');
        }

        expect($material->fresh()->quantity_on_hand)->toBe('9999999999.9999')
            ->and($material->fresh()->unit_cost)->toBe('1.0000')
            ->and(ledgerOf($material))->toBe([]);
    });

    it('adds up two receipts instead of overwriting one with the other', function () {
        $material = ledgerMaterial('100.0000');
        $copyOne = Material::query()->findOrFail($material->id);
        $copyTwo = Material::query()->findOrFail($material->id); // both read 100 before either receipt landed

        app(RestockMaterial::class)->handle($copyOne, ['quantity' => '10', 'unit_price' => '8.5', 'received_on' => '2026-10-08'], $this->actor);
        app(RestockMaterial::class)->handle($copyTwo, ['quantity' => '5', 'unit_price' => '8.5', 'received_on' => '2026-10-08'], $this->actor);

        expect($material->fresh()->quantity_on_hand)->toBe('115.0000')
            ->and(array_column(ledgerOf($material), 'quantity_after'))->toBe(['110.0000', '115.0000']);
    });

    it('rolls the whole receipt back when the ledger write fails', function () {
        $material = ledgerMaterial('100.0000', '1.0000');
        // Break the ledger insert on any engine: the entry points at a user that does not exist (a foreign key violation).
        $ghost = new User;
        $ghost->id = 999999999;

        expect(fn () => app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08'], $ghost))
            ->toThrow(QueryException::class);

        expect($material->fresh()->quantity_on_hand)->toBe('100.0000')
            ->and($material->fresh()->unit_cost)->toBe('1.0000')
            ->and(StockMovement::query()->count())->toBe(0);
    });

    it('runs inside a transaction of its own, reading the material first thing', function () {
        $material = ledgerMaterial('100.0000');
        $baseline = DB::transactionLevel(); // RefreshDatabase's own transaction
        $seen = [];

        DB::listen(function ($query) use (&$seen): void {
            $seen[] = ['sql' => $query->sql, 'level' => DB::transactionLevel()];
        });

        app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08'], $this->actor);

        $selects = array_values(array_filter($seen, fn (array $q): bool => str_starts_with($q['sql'], 'select') && str_contains($q['sql'], '"materials"')));

        expect($selects)->not->toBeEmpty()
            // The first read of the material (the lock) and every write run inside the Action's own transaction.
            ->and($selects[0]['level'])->toBeGreaterThan($baseline)
            ->and(min(array_column($seen, 'level')))->toBeGreaterThan($baseline);
    });

    it('selects the material FOR UPDATE on PostgreSQL', function () {
        $material = ledgerMaterial('100.0000');
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08'], $this->actor);

        $locks = array_values(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'for update')));

        expect($locks)->toHaveCount(1)
            ->and($locks[0])->toContain('"materials"');
    })->skip(fn () => DB::getDriverName() !== 'pgsql', 'FOR UPDATE is only emitted on PostgreSQL (SQLite has no row locks).');

    it('does not find a soft-deleted material', function () {
        $material = ledgerMaterial();
        $material->delete();

        expect(fn () => app(RestockMaterial::class)->handle($material, ['quantity' => '5', 'unit_price' => '2', 'received_on' => '2026-10-08'], $this->actor))
            ->toThrow(ModelNotFoundException::class)
            ->and(StockMovement::query()->count())->toBe(0);
    });
});
