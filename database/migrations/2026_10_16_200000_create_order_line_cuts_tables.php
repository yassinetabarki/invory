<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-53-a (ADR-0014): what each "Start cutting" took, so a move back to To start returns exactly that.
 *
 * - `order_line_cuts`: one row per cut of an order line (the units, how many came back so far, who).
 * - `order_line_cut_materials`: per cut and material, the exact amount per unit (waste included, 8 decimals) and the amount taken,
 *   with the material's unit at that time (a changed unit returns nothing).
 * - `stock_movements` gains the order, the product and the units a `cutting` / `cutting_return` entry is about. Orders and products
 *   are only ever soft-deleted, so the keys restrict.
 *
 * PostgreSQL only: `unique(workshop_id, id)` on order_lines and order_line_cuts, composite foreign keys that make the database refuse
 * a cut or cut material whose workshop differs from its line's, cut's or material's (R4), and CHECKs that mirror the quantity rules.
 * SQLite relies on validation, and rebuilds `stock_movements` for its new keys (nothing points at that table).
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string, 2: string}> constraint => [table, column, parent table], all on (workshop_id, <column>) */
    private const COMPOSITE_KEYS = [
        'order_line_cuts_workshop_line_foreign' => ['order_line_cuts', 'order_line_id', 'order_lines'],
        'order_line_cut_materials_workshop_cut_foreign' => ['order_line_cut_materials', 'order_line_cut_id', 'order_line_cuts'],
        'order_line_cut_materials_workshop_material_foreign' => ['order_line_cut_materials', 'material_id', 'materials'],
    ];

    /** @var array<string, array{0: string, 1: string}> constraint => [table, condition] */
    private const CHECKS = [
        'order_line_cuts_units_check' => ['order_line_cuts', 'units >= 1'],
        'order_line_cuts_units_returned_check' => ['order_line_cuts', 'units_returned >= 0 AND units_returned <= units'],
        'order_line_cut_materials_per_unit_check' => ['order_line_cut_materials', 'quantity_per_unit > 0'],
        'order_line_cut_materials_taken_check' => ['order_line_cut_materials', 'quantity_taken >= 0'],
    ];

    public function up(): void
    {
        Schema::create('order_line_cuts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('order_line_id')->constrained('order_lines')->cascadeOnDelete();
            $table->unsignedInteger('units');
            $table->unsignedInteger('units_returned')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // The cuts of a line, latest first.
            $table->index(['order_line_id', 'id']);
        });

        Schema::create('order_line_cut_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('order_line_cut_id')->constrained('order_line_cuts')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            // The material's unit at the cut: a return into a material counted in another unit would be wrong.
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity_per_unit', 20, 8);
            $table->decimal('quantity_taken', 14, 4);
            $table->timestamps();

            $table->unique(['order_line_cut_id', 'material_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('units')->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            // `materials` already has `unique(workshop_id, id)` (2026_10_15_100100).
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_workshop_id_id_unique UNIQUE (workshop_id, id)');
            DB::statement('ALTER TABLE order_line_cuts ADD CONSTRAINT order_line_cuts_workshop_id_id_unique UNIQUE (workshop_id, id)');

            foreach (self::COMPOSITE_KEYS as $constraint => [$table, $column, $parent]) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY (workshop_id, {$column}) REFERENCES {$parent} (workshop_id, id)");
            }

            foreach (self::CHECKS as $constraint => [$table, $condition]) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK ({$condition})");
            }
        }
    }

    public function down(): void
    {
        // The ledger columns and the cut tables go first: on PostgreSQL their keys point at orders, products, order_lines and cuts.
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('units');
        });

        Schema::dropIfExists('order_line_cut_materials');
        Schema::dropIfExists('order_line_cuts');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_lines DROP CONSTRAINT IF EXISTS order_lines_workshop_id_id_unique');
        }
    }
};
