<?php

use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The colours table (INV-1-a, ADR-0018)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration, and every factory workshop already holds the 20 defaults (Indigo IND among them).
| The CHECKs only exist on PostgreSQL (the parity run); the two unique indexes exist on both, but are proved here with the CHECKs.
*/

it('makes PostgreSQL refuse a colour that breaks a name, code or format rule, while two workshops may share a code and a name', function (array $override, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The CHECKs are PostgreSQL-only.');
    }

    [$workshop, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $row = ['workshop_id' => $workshop->id, 'code' => 'STW', 'name' => 'Stone wash', 'swatch' => '#B7C3CF', 'position' => 21, 'created_at' => now(), 'updated_at' => now()];

    // Two workshops may each have an IND named Indigo; only one workshop may not hold a second.
    expect(DB::table('colours')->whereIn('workshop_id', [$workshop->id, $other->id])->where('code', 'IND')->where('name', 'Indigo')->count())->toBe(2);

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names the constraint that fired.
    expect(fn () => DB::transaction(fn () => DB::table('colours')->insert([...$row, ...$override])))
        ->toThrow(QueryException::class, $constraint);
})->with([
    '"indigo" beside "Indigo"' => [['name' => 'indigo'], 'colours_workshop_name_unique'],
    'a second IND in one workshop' => [['code' => 'IND'], 'colours_workshop_code_unique'],
    'the code "I-D"' => [['code' => 'I-D'], 'colours_code_format_check'],
    'the swatch "blue"' => [['swatch' => 'blue'], 'colours_swatch_format_check'],
]);
