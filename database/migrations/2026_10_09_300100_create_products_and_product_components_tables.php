<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-04a (ADR-0010, ARCHITECTURE §4): the real `products` and `product_components` (the bill of materials) tables.
 *
 * The range CHECKs and the trigram search indexes of both tables are added by the PostgreSQL-only migration that follows.
 * If a later migration rebuilds `products` on SQLite (a column change or drop), it must drop the partial SKU index first
 * and create it again at the end, as the S-21 migration does for `materials`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 64);
            $table->string('name', 150);
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('status', 16)->default('active');
            $table->text('description')->nullable();
            $table->decimal('labor_cost', 12, 4)->default(0);
            $table->decimal('overhead_cost', 12, 4)->default(0);
            $table->decimal('selling_price', 12, 4)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('category_id');
            $table->index('status');
        });

        // SKU is unique among non-deleted rows only. Same syntax on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (sku) WHERE deleted_at IS NULL');

        Schema::create('product_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('waste_percent', 5, 2)->default(0);
            $table->string('notes', 100)->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['product_id', 'material_id']);
            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');

        if (DB::getDriverName() !== 'pgsql') {
            // SQLite has no DROP TABLE ... CASCADE; it does not block dropping a referenced table.
            Schema::dropIfExists('products');

            return;
        }

        // CASCADE drops dependent FK constraints (e.g. sales.product_id) on PostgreSQL without removing those tables.
        DB::statement('DROP TABLE IF EXISTS products CASCADE');
    }
};
