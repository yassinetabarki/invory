<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-21-a (ADR-0029): a material can keep its stock, and optionally its cost, per colour of Settings › Colours.
 *
 * - `materials.stock_mode` (`single` · `per_colour`) and `cost_mode` (`same` · `per_colour`), default `single` / `same`. Every material that
 *   exists reads `single` / `same`; no row is rewritten beyond the defaults.
 * - `material_colours`: one row per colour of a per-colour material (quantity, and the unit cost with cost per colour).
 * - `stock_movements.colour_id` / `entry_group`: the colour an entry of a per-colour material is about, and the receipt or switch it belongs to.
 * - `production_cut_materials.colour_id`: the colour of the stock a take came from (null for one stock and every cut before INV-21). The
 *   unique key becomes cut + material + colour; a partial unique index keeps one colourless take per cut and material (both drivers,
 *   since a unique key treats NULLs as different).
 *
 * PostgreSQL only: the CHECKs and the workshop composite keys (ADR-0013), also on `stock_movements` and `production_cut_materials`
 * (`colour_id`, inert while null); `materials` and `colours` already have their `UNIQUE (workshop_id, id)`. The composite keys are NO ACTION, not RESTRICT, so deleting a material removes its colour rows in one statement.
 *
 * `down()` refuses (saying why) while a material is per colour or a cut take names a colour: the colour split of the stock and the second
 * take of a material have nothing to go back to. Otherwise it removes everything added here. No `DROP … CASCADE`, so it runs on SQLite too.
 * It imports no app class: query builder and Schema only.
 */
return new class extends Migration
{
    /** Tables that got a nullable `colour_id` and, on PostgreSQL, the composite key (workshop_id, colour_id). */
    private const COLOUR_KEYED_TABLES = ['stock_movements', 'production_cut_materials'];

    public function up(): void
    {
        $this->deferForeignKeysOnSqlite();

        Schema::table('materials', function (Blueprint $table): void {
            $table->string('stock_mode', 16)->default('single');
            $table->string('cost_mode', 16)->default('same');
        });

        Schema::create('material_colours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 14, 4)->default(0);
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'colour_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('colour_id')->nullable()->constrained('colours')->restrictOnDelete();
            $table->uuid('entry_group')->nullable();
            $table->index(['material_id', 'colour_id']);
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->foreignId('colour_id')->nullable()->constrained('colours')->restrictOnDelete();
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->dropUnique(['production_cut_id', 'material_id']);
            $table->unique(['production_cut_id', 'material_id', 'colour_id']);
        });

        DB::statement('CREATE UNIQUE INDEX production_cut_materials_cut_material_uncoloured_unique ON production_cut_materials (production_cut_id, material_id) WHERE colour_id IS NULL');

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresKeys();
        }
    }

    public function down(): void
    {
        $this->refuseWhatCannotGoBack();
        $this->deferForeignKeysOnSqlite();

        if (DB::getDriverName() === 'pgsql') {
            foreach (self::COLOUR_KEYED_TABLES as $table) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_workshop_colour_foreign");
            }
        }

        DB::statement('DROP INDEX production_cut_materials_cut_material_uncoloured_unique');

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->dropUnique(['production_cut_id', 'material_id', 'colour_id']);
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('colour_id');
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->unique(['production_cut_id', 'material_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['material_id', 'colour_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('colour_id');
            $table->dropColumn('entry_group');
        });

        Schema::dropIfExists('material_colours');

        if (DB::getDriverName() === 'pgsql') {
            foreach (['materials_stock_mode_check', 'materials_cost_mode_check', 'materials_cost_mode_single_check'] as $constraint) {
                DB::statement("ALTER TABLE materials DROP CONSTRAINT IF EXISTS {$constraint}");
            }
        }

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn(['stock_mode', 'cost_mode']);
        });
    }

    private function addPostgresKeys(): void
    {
        DB::statement("ALTER TABLE materials ADD CONSTRAINT materials_stock_mode_check CHECK (stock_mode IN ('single', 'per_colour'))");
        DB::statement("ALTER TABLE materials ADD CONSTRAINT materials_cost_mode_check CHECK (cost_mode IN ('same', 'per_colour'))");
        // Cost per colour needs the stock per colour.
        DB::statement("ALTER TABLE materials ADD CONSTRAINT materials_cost_mode_single_check CHECK (stock_mode = 'per_colour' OR cost_mode = 'same')");

        DB::statement('ALTER TABLE material_colours ADD CONSTRAINT material_colours_quantity_check CHECK (quantity_on_hand >= 0)');
        DB::statement('ALTER TABLE material_colours ADD CONSTRAINT material_colours_unit_cost_check CHECK (unit_cost IS NULL OR unit_cost >= 0)');
        DB::statement('ALTER TABLE material_colours ADD CONSTRAINT material_colours_workshop_material_foreign FOREIGN KEY (workshop_id, material_id) REFERENCES materials (workshop_id, id)');
        DB::statement('ALTER TABLE material_colours ADD CONSTRAINT material_colours_workshop_colour_foreign FOREIGN KEY (workshop_id, colour_id) REFERENCES colours (workshop_id, id)');

        // Inert for a null colour (MATCH SIMPLE); a colour of another workshop on an entry or a take is refused (R4).
        foreach (self::COLOUR_KEYED_TABLES as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_workshop_colour_foreign FOREIGN KEY (workshop_id, colour_id) REFERENCES colours (workshop_id, id)");
        }
    }

    private function refuseWhatCannotGoBack(): void
    {
        $materials = DB::table('materials')->where('stock_mode', 'per_colour')->count();

        if ($materials > 0) {
            throw new RuntimeException("Stock per colour can't be removed: {$materials} material(s) keep their stock per colour. Switch them back to one stock first.");
        }

        $takes = DB::table('production_cut_materials')->whereNotNull('colour_id')->count();

        if ($takes > 0) {
            throw new RuntimeException("Stock per colour can't be removed: {$takes} material(s) of production cuts were taken from a colour, so a material may appear twice in one cut.");
        }
    }

    /** F-59: SQLite checks keys at the commit instead of at each statement, so a rebuilt table can take its place first. */
    private function deferForeignKeysOnSqlite(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        }
    }
};
