<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-56-a (ADR-0017): the finished units of a product on the shelf, per size.
 *
 * One row per product and size, and only while that size holds at least 1 unit: "nothing in stock" means no rows. There is no upper
 * bound here (1,000,000 is the input rule of "Update stock", and S-57 adds to these rows). Products are soft-deleted, so the cascade
 * on `product_id` only fires on a hard delete.
 *
 * PostgreSQL only: a composite foreign key that makes the database refuse a row whose workshop differs from its product's (R4;
 * `products` has `unique(workshop_id, id)` since 2026_10_15_100100), and a CHECK `units >= 1`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_size_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->unsignedInteger('units');
            $table->timestamps();

            $table->unique(['product_id', 'size_id']);
            $table->index('size_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_size_stocks ADD CONSTRAINT product_size_stocks_workshop_product_foreign FOREIGN KEY (workshop_id, product_id) REFERENCES products (workshop_id, id)');
            DB::statement('ALTER TABLE product_size_stocks ADD CONSTRAINT product_size_stocks_units_check CHECK (units >= 1)');
        }
    }

    public function down(): void
    {
        // The table goes with its constraints: on PostgreSQL its composite key points at products.
        Schema::dropIfExists('product_size_stocks');
    }
};
