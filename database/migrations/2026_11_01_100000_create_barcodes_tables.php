<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-8-a (ADR-0020): barcodes follow the name. Four changes, in this order:
 *
 * 1. `products.name_key` (150): the name in plain form (trimmed, one space between words, small letters), filled for every product that
 *    exists, soft-deleted ones too, by THIS migration's frozen copy of `App\Domain\Barcodes\NameKey::of()`; then NOT NULL, and an index
 *    (`workshop_id`, `name_key`). The app keeps it right afterwards (`Product::name()` sets both).
 * 2. `workshops.last_barcode_serial` (default 0): the last in-store serial given, read and saved under the workshop's row lock.
 * 3. `barcodes`: one row per number, unique (`workshop_id`, `number`), for one colour and one size.
 * 4. `barcode_names`: what a number stands for, unique (`workshop_id`, `name_key`, `colour_id`, `size_id`): one barcode per name, colour
 *    and size.
 *
 * Every key is `restrictOnDelete` (`created_by` `nullOnDelete`, as on materials): nothing in the app deletes a barcode.
 * PostgreSQL only: `unique(workshop_id, id, colour_id, size_id)` on `barcodes` and the composite key (`workshop_id`, `barcode_id`, `colour_id`, `size_id`) on
 * `barcode_names` that points at it, so a name can't point at a number of another workshop, colour or size.
 *
 * SQLite is the test engine and cannot add a NOT NULL column without a default, nor make a column NOT NULL without rebuilding
 * `products`: that rebuild drops the table, which cascades to every row that points at a product (PRAGMA foreign_keys cannot be
 * switched off inside the migrator's transaction) and loses the partial SKU index. So on SQLite the column is added NOT NULL with a
 * default of '' (nothing reads the default: the app always sets the key), and on PostgreSQL it is added nullable, filled, then made
 * NOT NULL with no default, so a raw insert without a key is refused there. It imports no app class.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';

        Schema::table('products', function (Blueprint $table) use ($sqlite): void {
            $column = $table->string('name_key', 150);
            $sqlite ? $column->default('') : $column->nullable();
        });

        $this->fillNameKeys();

        if (! $sqlite) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('name_key', 150)->nullable(false)->change();
            });
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->index(['workshop_id', 'name_key']);
        });

        Schema::table('workshops', function (Blueprint $table): void {
            $table->unsignedBigInteger('last_barcode_serial')->default(0);
        });

        Schema::create('barcodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('number', 13);
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workshop_id', 'number']);
        });

        Schema::create('barcode_names', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('barcode_id')->constrained('barcodes')->restrictOnDelete();
            $table->string('name_key', 150);
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['workshop_id', 'name_key', 'colour_id', 'size_id']);
            $table->index('barcode_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE barcodes ADD CONSTRAINT barcodes_workshop_id_id_colour_id_size_id_unique UNIQUE (workshop_id, id, colour_id, size_id)');
            DB::statement('ALTER TABLE barcode_names ADD CONSTRAINT barcode_names_barcode_colour_size_foreign FOREIGN KEY (workshop_id, barcode_id, colour_id, size_id) REFERENCES barcodes (workshop_id, id, colour_id, size_id) ON DELETE RESTRICT');
        }
    }

    public function down(): void
    {
        // The names go first: on PostgreSQL their composite key points at `barcodes`.
        Schema::dropIfExists('barcode_names');
        Schema::dropIfExists('barcodes');

        Schema::table('workshops', function (Blueprint $table): void {
            $table->dropColumn('last_barcode_serial');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['workshop_id', 'name_key']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('name_key');
        });
    }

    /** Every product, soft-deleted ones too (`DB::table` ignores the soft-delete scope), in chunks. */
    private function fillNameKeys(): void
    {
        DB::table('products')->select('id', 'name')->orderBy('id')->chunkById(500, function ($products): void {
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update(['name_key' => $this->nameKey((string) $product->name)]);
            }
        });
    }

    /** A frozen copy of `NameKey::of()` as it is today: trimmed, every run of spaces as one space, small letters, accents kept. */
    private function nameKey(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }
};
