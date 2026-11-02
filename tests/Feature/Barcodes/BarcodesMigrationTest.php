<?php

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductSizeStock;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The barcodes migration (INV-8-a, ADR-0020)
|--------------------------------------------------------------------------
|
| It is rolled back to the shape before INV-8, the names are written in a way the rule would key differently, and `up()` runs on them:
| every product of every workshop, soft-deleted ones too, gets the key its own name has. SQLite rebuilds `products` for the NOT NULL
| column, so what that rebuild could lose (the partial SKU index, the rows that point at products) is checked too.
*/

it('gives every product of every workshop its name key, soft-deleted ones too, and takes back what it added on down()', function () {
    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $jacket = Product::factory()->create(['name' => 'Denim Jacket', 'sku' => 'KEY-1']);
    $tote = Product::factory()->create(['name' => 'Écru Tote', 'sku' => 'KEY-2']);
    $tote->delete();
    $foreign = CurrentWorkshop::run($other, fn (): Product => Product::factory()->create(['name' => 'Denim Jacket', 'sku' => 'KEY-1']));

    // Rows that point at a product, to prove the rebuild of `products` on SQLite loses none.
    $jacket->colours()->attach(Colour::query()->ordered()->value('id'), ['workshop_id' => $jacket->workshop_id]);
    ProductSizeStock::factory()->create(['product_id' => $jacket->id, 'units' => 4]);
    $links = fn (): array => [DB::table('product_colours')->count(), DB::table('product_size_stocks')->count()];
    $before = $links();

    clientsMigration()->down();

    barcodesMigration()->down();

    expect(Schema::hasColumn('products', 'name_key'))->toBeFalse()
        ->and(Schema::hasColumn('workshops', 'last_barcode_serial'))->toBeFalse()
        ->and(Schema::hasTable('barcodes'))->toBeFalse()
        ->and(Schema::hasTable('barcode_names'))->toBeFalse()
        ->and($links())->toBe($before);

    // Names as they were typed before the rule existed.
    DB::table('products')->where('id', $jacket->id)->update(['name' => '  DENIM   Jacket ']);
    DB::table('products')->where('id', $tote->id)->update(['name' => 'ÉCRU  Tote']);

    barcodesMigration()->up();

    clientsMigration()->up();

    expect(DB::table('products')->pluck('name_key', 'id')->all())->toBe([$jacket->id => 'denim jacket', $tote->id => 'écru tote', $foreign->id => 'denim jacket'])
        ->and(DB::table('workshops')->pluck('last_barcode_serial')->unique()->all())->toBe([0])
        ->and(Schema::hasTable('barcodes'))->toBeTrue()
        ->and(Schema::hasTable('barcode_names'))->toBeTrue()
        ->and(Schema::hasIndex('products', ['workshop_id', 'name_key']))->toBeTrue()
        ->and(collect(Schema::getColumns('products'))->firstWhere('name', 'name_key')['nullable'])->toBeFalse()
        ->and($links())->toBe($before);

    // The SKU index is still partial: a live SKU can't repeat in a workshop, a deleted product's SKU is free again.
    // (In a savepoint: on PostgreSQL a refused statement would abort the whole test transaction.)
    expect(fn () => DB::transaction(fn () => Product::factory()->create(['sku' => 'KEY-1'])))->toThrow(QueryException::class);
    Product::factory()->create(['sku' => 'KEY-2']);
});
