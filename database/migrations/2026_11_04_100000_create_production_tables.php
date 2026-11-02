<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-5-a (ADR-0023): production for the shelf.
 *
 * - `production_phases`: the workshop's own phases between In cutting and Put in stock (0 to 6), in order. In cutting and Put in stock
 *   have no row. A phase is deleted for real, only while no batch in production is in it.
 * - `production_cuts` / `production_cut_materials`: what one Start batch took out of stock, per material (ADR-0014's maths), so Undo
 *   batch gives back exactly that. Every batch of one start, and every batch split from them, points at the same cut.
 * - `production_batches`: units of one product in one colour and one size, made together, numbered per workshop (B-0147). `phase_id`
 *   null means In cutting while the batch is in production; it is set to null again once the batch leaves Progress.
 * - `production_batch_moves`: every start, move, Put in stock and undo, with the phase names at that time.
 * - `production_counters`: one row per workshop with the counter batch numbers come from (never reused). Every production writer locks
 *   it first, so production writes of one workshop run one after another and can't deadlock with product or workshop locks.
 *
 * PostgreSQL only: `unique(workshop_id, id)` on the parents, composite keys so nothing points at another workshop's row, and CHECKs.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string, 2: string}> constraint => [table, column, parent table], all on (workshop_id, <column>) */
    private const COMPOSITE_KEYS = [
        'production_cuts_workshop_product_foreign' => ['production_cuts', 'product_id', 'products'],
        'production_cut_materials_workshop_cut_foreign' => ['production_cut_materials', 'production_cut_id', 'production_cuts'],
        'production_cut_materials_workshop_material_foreign' => ['production_cut_materials', 'material_id', 'materials'],
        'production_batches_workshop_product_foreign' => ['production_batches', 'product_id', 'products'],
        'production_batches_workshop_cut_foreign' => ['production_batches', 'production_cut_id', 'production_cuts'],
        'production_batches_workshop_phase_foreign' => ['production_batches', 'phase_id', 'production_phases'],
        'production_batch_moves_workshop_batch_foreign' => ['production_batch_moves', 'production_batch_id', 'production_batches'],
    ];

    /** @var array<string, array{0: string, 1: string}> constraint => [table, condition] */
    private const CHECKS = [
        'production_phases_position_check' => ['production_phases', 'position BETWEEN 1 AND 6'],
        'production_cuts_units_check' => ['production_cuts', 'units >= 1'],
        'production_cuts_units_returned_check' => ['production_cuts', 'units_returned >= 0 AND units_returned <= units'],
        'production_cut_materials_per_unit_check' => ['production_cut_materials', 'quantity_per_unit > 0'],
        'production_cut_materials_taken_check' => ['production_cut_materials', 'quantity_taken >= 0'],
        'production_batches_units_check' => ['production_batches', 'units BETWEEN 1 AND 1000000'],
        'production_batches_number_check' => ['production_batches', 'number >= 1'],
        'production_batches_status_check' => ['production_batches', "status IN ('in_production', 'in_stock', 'undone')"],
        'production_batch_moves_units_check' => ['production_batch_moves', 'units >= 1'],
    ];

    public function up(): void
    {
        Schema::create('production_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->unique()->constrained('workshops')->restrictOnDelete();
            $table->unsignedInteger('last_batch_number')->default(0);
            $table->timestamps();
        });

        Schema::create('production_phases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('name', 24);
            // Not unique: a swap of two phases goes through a moment where both hold one place; the Actions keep 1…n under the workshop lock.
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index(['workshop_id', 'position']);
        });

        // A name once per workshop, ignoring capitals (both engines know expression indexes).
        DB::statement('CREATE UNIQUE INDEX production_phases_workshop_name_unique ON production_phases (workshop_id, lower(name))');

        Schema::create('production_cuts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('units');
            $table->unsignedInteger('units_returned')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::create('production_cut_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('production_cut_id')->constrained('production_cuts')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            // The material's unit at the cut: a return into a material counted in another unit would be wrong.
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity_per_unit', 20, 8);
            $table->decimal('quantity_taken', 14, 4);
            $table->timestamps();

            $table->unique(['production_cut_id', 'material_id']);
            $table->index('material_id');
        });

        Schema::create('production_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->foreignId('production_cut_id')->constrained('production_cuts')->restrictOnDelete();
            $table->foreignId('split_from_id')->nullable()->constrained('production_batches')->restrictOnDelete();
            $table->unsignedInteger('units');
            $table->string('status', 16);
            $table->foreignId('phase_id')->nullable()->constrained('production_phases')->restrictOnDelete();
            $table->date('phase_since');
            $table->date('started_on');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_restock_id')->nullable()->constrained('product_restocks')->restrictOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workshop_id', 'number']);
            $table->index(['workshop_id', 'status', 'phase_id', 'phase_since', 'id'], 'production_batches_phase_view_index');
            $table->index(['product_id', 'status']);
            $table->index('colour_id');
            $table->index('size_id');
            $table->index('production_cut_id');
        });

        Schema::create('production_batch_moves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->unsignedInteger('units');
            $table->string('from_label', 24)->nullable();
            $table->string('to_label', 24)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['production_batch_id', 'id']);
            $table->index(['workshop_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['production_phases', 'production_cuts', 'production_batches'] as $parent) {
                DB::statement("ALTER TABLE {$parent} ADD CONSTRAINT {$parent}_workshop_id_id_unique UNIQUE (workshop_id, id)");
            }

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
        // Children first: on PostgreSQL their composite keys point at the parents.
        Schema::dropIfExists('production_batch_moves');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('production_cut_materials');
        Schema::dropIfExists('production_cuts');
        Schema::dropIfExists('production_phases');
        Schema::dropIfExists('production_counters');
    }
};
