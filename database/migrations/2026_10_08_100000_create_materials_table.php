<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 64);
            $table->string('name', 150);
            $table->string('category', 32);
            $table->string('unit', 16);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('quantity_on_hand', 14, 4);
            $table->decimal('reorder_level', 14, 4)->nullable();
            $table->string('supplier_name', 150)->nullable();
            $table->string('supplier_reference', 64)->nullable();
            $table->string('color', 64)->nullable();
            $table->string('composition', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('name');
        });

        // SKU is unique among non-deleted rows only. Same syntax on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (sku) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX materials_sku_unique');

        Schema::dropIfExists('materials');
    }
};
