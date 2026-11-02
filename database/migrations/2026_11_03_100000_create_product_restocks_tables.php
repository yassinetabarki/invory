<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-3-a (ADR-0022): the restocks of finished products and what each one added.
 *
 * `product_restocks` is one restock: its date, reference, note, who recorded it, its total (written once: a restock is never edited)
 * and, once undone, who undid it and when. Nothing deletes a restock; Undo is a flag.
 * `product_restock_lines` is one product, colour and size a restock added units to, only for the variants that got units. The shelf
 * count itself stays in `product_size_stocks`: these tables are the record, not a second count.
 *
 * PostgreSQL only: `unique(workshop_id, id)` on restocks, composite keys so a line can't point at another workshop's restock or
 * product, and CHECKs on the units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_restocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->date('restocked_on');
            $table->string('reference', 64)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('units');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workshop_id', 'restocked_on', 'id'], 'product_restocks_workshop_date_index');
        });

        Schema::create('product_restock_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_restock_id')->constrained('product_restocks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('units');
            $table->timestamps();

            $table->unique(['product_restock_id', 'product_id', 'colour_id', 'size_id'], 'product_restock_lines_variant_unique');
            $table->index(['product_id', 'product_restock_id'], 'product_restock_lines_product_index');
            $table->index('colour_id');
            $table->index('size_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_restocks ADD CONSTRAINT product_restocks_workshop_id_id_unique UNIQUE (workshop_id, id)');
            DB::statement('ALTER TABLE product_restocks ADD CONSTRAINT product_restocks_units_check CHECK (units >= 1)');
            DB::statement('ALTER TABLE product_restock_lines ADD CONSTRAINT product_restock_lines_workshop_restock_foreign FOREIGN KEY (workshop_id, product_restock_id) REFERENCES product_restocks (workshop_id, id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE product_restock_lines ADD CONSTRAINT product_restock_lines_workshop_product_foreign FOREIGN KEY (workshop_id, product_id) REFERENCES products (workshop_id, id)');
            DB::statement('ALTER TABLE product_restock_lines ADD CONSTRAINT product_restock_lines_units_check CHECK (units BETWEEN 1 AND 1000000)');
        }
    }

    public function down(): void
    {
        // The lines go first: on PostgreSQL their composite keys point at both tables.
        Schema::dropIfExists('product_restock_lines');
        Schema::dropIfExists('product_restocks');
    }
};
