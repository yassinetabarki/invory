<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Sizes per workshop: a database with no workshop (INV-18-c, AC-3, ADR-0028 decision 4.1)
|--------------------------------------------------------------------------
|
| A fresh database has no workshop, so the migration left no size (a workshop gets its 19 when it is made). Rolled back it writes the
| three old lists and their 19 sizes from nothing, and forward again it removes them.
*/

it('writes the old lists from nothing on down() and removes the sizes again on up(), when there is no workshop', function () {
    expect(DB::table('workshops')->count())->toBe(0)
        ->and(DB::table('sizes')->count())->toBe(0);

    sizesPerWorkshopMigration()->down();

    expect(DB::table('size_lists')->orderBy('id')->pluck('name', 'code')->all())->toBe(['s_to_xl' => 'XS to XXXL', 'eu_36_to_46' => '36 to 46', 'one_size' => 'One size'])
        ->and(DB::table('sizes')->count())->toBe(19)
        ->and(DB::table('sizes')->join('size_lists', 'size_lists.id', '=', 'sizes.size_list_id')->where('size_lists.code', 's_to_xl')->orderBy('sizes.position')->pluck('sizes.label')->all())
        ->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']);

    sizesPerWorkshopMigration()->up();

    expect(DB::table('sizes')->count())->toBe(0)
        ->and(Schema::hasTable('size_lists'))->toBeFalse()
        ->and(Schema::hasTable('product_sizes'))->toBeTrue();
});
