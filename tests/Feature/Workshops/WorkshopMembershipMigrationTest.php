<?php

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| S-44-a: the workshops migration, on a database that already has accounts (AC-7, accounts part)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run it. The test rolls it back and forward again with the migration object: schema changes are
| transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after the test.
*/

function workshopsMigration(): Migration
{
    return require database_path('migrations/2026_10_15_100000_create_workshops_table.php');
}

/** The S-44-b migration points at `workshops` from five tables, so it is rolled back first and forward last (newest first, as `migrate:rollback` does). */
function rollBackWorkshops(): void
{
    // Newer migrations that depend on `(workshop_id, id)` (orders) must be rolled back first.
    cutsMigration()->down();
    stageCountsMigration()->down();
    ordersMigration()->down();
    (require database_path('migrations/2026_10_15_100100_add_workshop_to_owned_tables.php'))->down();
    workshopsMigration()->down();
}

function migrateWorkshops(): void
{
    workshopsMigration()->up();
    (require database_path('migrations/2026_10_15_100100_add_workshop_to_owned_tables.php'))->up();
    ordersMigration()->up();
    stageCountsMigration()->up();
    cutsMigration()->up();
}

it('puts every existing account in one workshop, and creates none for an empty database', function (int $accounts, int $workshops) {
    $emails = User::factory()->count($accounts)->create()->pluck('email')->sort()->values()->all();

    rollBackWorkshops();

    expect(Schema::hasTable('workshops'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'workshop_id'))->toBeFalse()
        ->and(User::query()->count())->toBe($accounts);

    migrateWorkshops();

    expect(Workshop::query()->count())->toBe($workshops)
        ->and(User::query()->count())->toBe($accounts)
        ->and(User::query()->orderBy('email')->pluck('email')->sort()->values()->all())->toBe($emails)
        ->and(User::query()->distinct()->pluck('workshop_id')->all())->toBe($accounts === 0 ? [] : [Workshop::query()->value('id')]);

    // The table rebuild keeps what `users` already had: the unique email, and it adds the foreign key and the NOT NULL.
    $foreignKeys = collect(Schema::getForeignKeys('users'))->where('foreign_table', 'workshops');

    expect(Schema::hasIndex('users', ['email'], 'unique'))->toBeTrue()
        ->and($foreignKeys->pluck('columns')->all())->toBe([['workshop_id']])
        ->and(fn () => DB::transaction(fn () => DB::table('users')->insert([
            'name' => 'Nobody', 'email' => 'nobody@example.com', 'password' => 'x',
        ])))->toThrow(QueryException::class);

    // Down and up again is safe.
    rollBackWorkshops();
    migrateWorkshops();

    expect(Workshop::query()->count())->toBe($workshops);
})->with([
    'accounts exist' => [3, 1],
    'empty database' => [0, 0],
]);
