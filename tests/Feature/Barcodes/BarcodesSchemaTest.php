<?php

use App\Models\Size;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The barcodes and barcode_names tables (INV-8-a, ADR-0020)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The unique keys exist on both engines, so they run on SQLite too (its message names the
| columns); the composite key (`workshop_id`, `barcode_id`, `colour_id`, `size_id`) only exists on PostgreSQL (the parity run), so that row is skipped
| on SQLite. The PostgreSQL constraint names below are the ones the migration gives; they are unverified until the parity run has been
| done with Docker.
*/

it('keeps a number once per workshop, one barcode per name, colour and size, and a name on its number\'s own colour and size', function (Closure $write, string $postgres, ?string $sqlite) {
    if ($sqlite === null && DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite foreign key is PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    [$white, $ecru] = DB::table('colours')->where('workshop_id', $mine->id)->orderBy('position')->limit(2)->pluck('id')->all();
    $otherWhite = DB::table('colours')->where('workshop_id', $other->id)->orderBy('position')->value('id');
    [$small, $medium] = [Size::query()->where('label', 'S')->value('id'), Size::query()->where('label', 'M')->value('id')];

    $barcode = fn (int $workshop, string $number, int $colour, int $size): int => DB::table('barcodes')->insertGetId([
        'workshop_id' => $workshop, 'number' => $number, 'colour_id' => $colour, 'size_id' => $size, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $name = fn (int $workshop, int $barcode, string $key, int $colour, int $size): bool => DB::table('barcode_names')->insert([
        'workshop_id' => $workshop, 'barcode_id' => $barcode, 'name_key' => $key, 'colour_id' => $colour, 'size_id' => $size, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $first = $barcode($mine->id, '2000000000015', $white, $small);
    $name($mine->id, $first, 'denim jacket', $white, $small);

    // Two workshops can each have 2000000000015.
    $barcode($other->id, '2000000000015', $otherWhite, $small);

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names what fired.
    expect(fn () => DB::transaction(fn () => $write($mine->id, $first, $barcode, $name, $white, $ecru, $small, $medium, $other->id)))
        ->toThrow(QueryException::class, DB::getDriverName() === 'pgsql' ? $postgres : $sqlite);
})->with([
    'the same number twice in one workshop' => [
        fn (int $workshop, int $first, Closure $barcode, Closure $name, int $white, int $ecru, int $small, int $medium) => $barcode($workshop, '2000000000015', $ecru, $medium),
        'barcodes_workshop_id_number_unique',
        'barcodes.workshop_id, barcodes.number',
    ],
    'a second barcode for the same name, colour and size' => [
        fn (int $workshop, int $first, Closure $barcode, Closure $name, int $white, int $ecru, int $small) => $name($workshop, $barcode($workshop, '2000000000022', $white, $small), 'denim jacket', $white, $small),
        'barcode_names_workshop_id_name_key_colour_id_size_id_unique',
        'barcode_names.workshop_id, barcode_names.name_key, barcode_names.colour_id, barcode_names.size_id',
    ],
    'a name pointing at the number of another colour' => [
        fn (int $workshop, int $first, Closure $barcode, Closure $name, int $white, int $ecru, int $small) => $name($workshop, $first, 'trucker jacket', $ecru, $small),
        'barcode_names_barcode_colour_size_foreign',
        null,
    ],
    'a name of another workshop pointing at a number of this one' => [
        fn (int $workshop, int $first, Closure $barcode, Closure $name, int $white, int $ecru, int $small, int $medium, int $other) => $name($other, $first, 'denim jacket', $white, $small),
        'barcode_names_barcode_colour_size_foreign',
        null,
    ],
]);
