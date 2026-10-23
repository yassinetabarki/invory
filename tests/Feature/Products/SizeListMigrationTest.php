<?php

use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The size lists migration on data you already have (S-58-a, AC-5, ADR-0015)
|--------------------------------------------------------------------------
|
| The S-21 pattern: roll the migration back (product categories lose their `size_list_id`), put rows in the old shape, run it forward.
| Schema changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after each test.
*/

/**
 * SQLite copies and drops a table to change a column, and dropping a table that products point at fires those foreign keys (F-59).
 * Deferring the check to the commit lets the rebuilt table take its place first. PostgreSQL changes the column in place.
 */
function deferForeignKeysOnSqlite(): void
{
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA defer_foreign_keys = ON');
    }
}

/** A `product_categories` row in the shape before the migration. */
function oldCategoryRow(string $code, string $name, ?string $archivedAt = null): array
{
    return ['code' => $code, 'name' => $name, 'position' => 50, 'archived_at' => $archivedAt, 'created_at' => now(), 'updated_at' => now()];
}

/** The state before the change: the six defaults, "Kids" added since, Apparel renamed "Clothing" and archived, and a product in each. */
function putCategoriesAndProductsInTheOldShape(): void
{
    DB::table('product_categories')->where('code', 'apparel')->update(['name' => 'Clothing', 'archived_at' => now()]);
    DB::table('product_categories')->insert(oldCategoryRow('kids', 'Kids'));

    foreach (DB::table('product_categories')->orderBy('id')->pluck('id', 'code') as $code => $categoryId) {
        DB::table('products')->insert([
            ...rawWorkshop('products'), 'sku' => "OLD-{$code}", 'name' => "Old {$code}", 'category_id' => $categoryId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

/**
 * What the migration decides, in codes (ids are not stable across a round trip): category code => list code, and product SKU => category code.
 *
 * @return array{lists: array<string, string>, products: array<string, string>}
 */
function categoryListsAndProducts(): array
{
    return [
        'lists' => DB::table('product_categories')->join('size_lists', 'size_lists.id', '=', 'product_categories.size_list_id')
            ->orderBy('product_categories.id')->selectRaw('product_categories.code as category, size_lists.code as list')->pluck('list', 'category')->all(),
        'products' => DB::table('products')->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->orderBy('products.id')->selectRaw('products.sku as sku, product_categories.code as category')->pluck('category', 'sku')->all(),
    ];
}

it('gives every category the right list, by code, and keeps every product in its category', function () {
    $migration = sizeListsMigration();
    orderLineSizesMigration()->down();
    deferForeignKeysOnSqlite();
    $migration->down();

    putCategoriesAndProductsInTheOldShape();
    $migration->up();

    $state = categoryListsAndProducts();

    expect($state['lists'])->toBe([
        'apparel' => 's_to_xl', 'footwear' => 'eu_36_to_46', 'bag' => 'one_size', 'accessory' => 'one_size',
        'jewelry' => 'one_size', 'other' => 'one_size', 'kids' => 'one_size',
    ])->and($state['products'])->toBe([
        'OLD-apparel' => 'apparel', 'OLD-footwear' => 'footwear', 'OLD-bag' => 'bag', 'OLD-accessory' => 'accessory',
        'OLD-jewelry' => 'jewelry', 'OLD-other' => 'other', 'OLD-kids' => 'kids',
    ])->and(ProductCategory::query()->where('code', 'apparel')->first())
        ->name->toBe('Clothing')
        ->archived_at->not->toBeNull();
});

it('round-trips: down takes the lists away and leaves the rest, up gives the same rows', function () {
    $migration = sizeListsMigration();
    orderLineSizesMigration()->down();
    deferForeignKeysOnSqlite();
    $migration->down();

    putCategoriesAndProductsInTheOldShape();
    $migration->up();
    $before = categoryListsAndProducts();

    $migration->down();

    expect(Schema::hasTable('size_lists'))->toBeFalse()
        ->and(Schema::hasTable('sizes'))->toBeFalse()
        ->and(Schema::hasColumn('product_categories', 'size_list_id'))->toBeFalse()
        ->and(DB::table('product_categories')->count())->toBe(7)
        ->and(DB::table('products')->join('product_categories', 'product_categories.id', '=', 'products.category_id')->orderBy('products.id')->pluck('product_categories.code')->all())
        ->toBe(['apparel', 'footwear', 'bag', 'accessory', 'jewelry', 'other', 'kids']);

    $migration->up();

    expect(categoryListsAndProducts())->toBe($before);
});

describe('the partial name index of product categories survives the table rebuild', function () {
    $assertNamesAreUniqueAmongActiveRows = function (): void {
        $listId = DB::table('size_lists')->where('code', 'one_size')->value('id');
        $row = fn (string $code, string $name, ?string $archivedAt = null): array => [...oldCategoryRow($code, $name, $archivedAt), 'size_list_id' => $listId];

        // A second active "bag" is refused, whatever its capitals (each failing insert in its own savepoint, for PostgreSQL)...
        expect(fn () => DB::transaction(fn () => DB::table('product_categories')->insert($row('bag_2', 'BAG'))))->toThrow(QueryException::class)
            ->and(fn () => DB::transaction(fn () => DB::table('product_categories')->insert($row('bag_3', 'bAg'))))->toThrow(QueryException::class);

        // ...but an archived one never clashes (what `WHERE archived_at IS NULL` is for; a plain unique index would reject it).
        DB::table('product_categories')->insert($row('bag_4', 'Bag', now()->toDateTimeString()));

        expect(DB::table('product_categories')->whereRaw('lower(name) = ?', ['bag'])->count())->toBe(2);
    };

    it('after up', function () use ($assertNamesAreUniqueAmongActiveRows) {
        $migration = sizeListsMigration();
        orderLineSizesMigration()->down();
        $migration->down();
        $migration->up();

        $assertNamesAreUniqueAmongActiveRows();
    });

    it('after down and up again', function () use ($assertNamesAreUniqueAmongActiveRows) {
        $migration = sizeListsMigration();
        orderLineSizesMigration()->down();
        $migration->down();
        $migration->up();
        $migration->down();
        $migration->up();

        $assertNamesAreUniqueAmongActiveRows();
    });

    it('after down', function () {
        orderLineSizesMigration()->down();
        sizeListsMigration()->down();

        expect(fn () => DB::transaction(fn () => DB::table('product_categories')->insert(oldCategoryRow('bag_2', 'BAG'))))->toThrow(QueryException::class);

        DB::table('product_categories')->insert(oldCategoryRow('bag_3', 'Bag', now()->toDateTimeString()));

        expect(DB::table('product_categories')->whereRaw('lower(name) = ?', ['bag'])->count())->toBe(2);
    });
});
