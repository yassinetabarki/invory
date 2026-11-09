<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-16-a (ADR-0027): a composition line can use a quantity per colour and size.
 *
 * - `product_components.quantity_mode` (`same` · `by_variant`, default `same`); `quantity` becomes nullable (null exactly when
 *   `by_variant`). Every line that exists reads `same` with its quantity; no other row is touched.
 * - `product_component_quantities`: one row per filled cell of a `by_variant` line (colour × size, > 0). A missing cell means the variant
 *   doesn't use the material.
 * - `production_cut_variants` / `production_cut_variant_materials`: what a Start batch remembers per colour and size, so Undo batch gives
 *   back exactly what was taken. `production_cut_materials.quantity_per_unit` becomes nullable (null on a cut that has variant rows).
 *   No existing cut is backfilled (ADR-0027 §7): a cut without variant rows is read as one group.
 *
 * PostgreSQL only: the CHECKs and the composite keys. The existing `product_components_quantity_check` (`quantity > 0`) stays as it is: a
 * null passes it, and it keeps firing first (PostgreSQL checks in name order) for a quantity of 0 or less on a `same` line. The new
 * `product_components_quantity_mode_check` adds the pairing of mode and null. The workshop keys (ADR-0013) point at the parents' `UNIQUE (workshop_id, id)`
 * (`product_components` and `production_cut_variants` get theirs here). A cell's product must be its line's product (`(product_component_id,
 * product_id)` → `product_components (id, product_id)`), its colour one of the product's colours and its size one of its sizes
 * (`product_colours` / `product_sizes`, as ADR-0019 decision 3). The last two are NO ACTION, not RESTRICT: they are checked at the end of
 * the statement, so deleting a product removes its links and its cells in one statement.
 *
 * `down()` brings back the NOT NULL columns, so it refuses (naming the count) while a line is `by_variant` or a cut holds no per-unit
 * amount of its own: those rows have no value to go back to. Otherwise it drops the three tables and the column. It imports no app class:
 * query builder and Schema only.
 *
 * SQLite (the test engine) rebuilds a table to change a column; `product_components` and `production_cut_materials` have no partial or
 * expression index and nothing points at them yet, and the framework carries their keys and indexes over. The tables made here come
 * after both rebuilds. Index names are explicit where the generated one would pass PostgreSQL's 63 characters.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string, 2: string, 3: string}> constraint => [table, columns, parent table, parent columns] */
    private const COMPOSITE_KEYS = [
        'product_component_quantities_workshop_component_foreign' => ['product_component_quantities', 'workshop_id, product_component_id', 'product_components', 'workshop_id, id'],
        'product_component_quantities_component_product_foreign' => ['product_component_quantities', 'product_component_id, product_id', 'product_components', 'id, product_id'],
        'product_component_quantities_workshop_colour_foreign' => ['product_component_quantities', 'workshop_id, colour_id', 'colours', 'workshop_id, id'],
        'product_component_quantities_workshop_size_foreign' => ['product_component_quantities', 'workshop_id, size_id', 'sizes', 'workshop_id, id'],
        'product_component_quantities_product_colour_foreign' => ['product_component_quantities', 'product_id, colour_id', 'product_colours', 'product_id, colour_id'],
        'product_component_quantities_product_size_foreign' => ['product_component_quantities', 'product_id, size_id', 'product_sizes', 'product_id, size_id'],
        'production_cut_variants_workshop_cut_foreign' => ['production_cut_variants', 'workshop_id, production_cut_id', 'production_cuts', 'workshop_id, id'],
        'production_cut_variants_workshop_colour_foreign' => ['production_cut_variants', 'workshop_id, colour_id', 'colours', 'workshop_id, id'],
        'production_cut_variants_workshop_size_foreign' => ['production_cut_variants', 'workshop_id, size_id', 'sizes', 'workshop_id, id'],
        'production_cut_variant_materials_workshop_variant_foreign' => ['production_cut_variant_materials', 'workshop_id, production_cut_variant_id', 'production_cut_variants', 'workshop_id, id'],
        'production_cut_variant_materials_workshop_material_foreign' => ['production_cut_variant_materials', 'workshop_id, material_id', 'materials', 'workshop_id, id'],
    ];

    /** @var array<string, array{0: string, 1: string}> constraint => [table, condition] */
    private const CHECKS = [
        'product_component_quantities_quantity_check' => ['product_component_quantities', 'quantity > 0 AND quantity <= 1000000'],
        'production_cut_variants_units_check' => ['production_cut_variants', 'units >= 1 AND units_returned >= 0 AND units_returned <= units'],
        'production_cut_variant_materials_per_unit_check' => ['production_cut_variant_materials', 'quantity_per_unit > 0'],
    ];

    public function up(): void
    {
        $this->deferForeignKeysOnSqlite();

        if (DB::getDriverName() === 'pgsql') {
            // Replaced below, under the same name: a null amount per unit is now allowed, the rest of the rule is kept.
            DB::statement('ALTER TABLE production_cut_materials DROP CONSTRAINT IF EXISTS production_cut_materials_per_unit_check');
        }

        Schema::table('product_components', function (Blueprint $table): void {
            $table->string('quantity_mode', 16)->default('same');
        });

        // SQLite rebuilds the table here; its unique key, index and foreign keys are carried over by the framework (the migration test checks).
        Schema::table('product_components', function (Blueprint $table): void {
            $table->decimal('quantity', 14, 4)->nullable()->change();
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->decimal('quantity_per_unit', 20, 8)->nullable()->change();
        });

        $this->createTables();

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresKeys();
        }
    }

    public function down(): void
    {
        $this->refuseWhatCannotGoBack();
        $this->deferForeignKeysOnSqlite();

        // Children first: on PostgreSQL their composite keys point at the parents.
        Schema::dropIfExists('production_cut_variant_materials');
        Schema::dropIfExists('production_cut_variants');
        Schema::dropIfExists('product_component_quantities');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_components DROP CONSTRAINT IF EXISTS product_components_quantity_mode_check');
            DB::statement('ALTER TABLE product_components DROP CONSTRAINT IF EXISTS product_components_workshop_id_id_unique');
            DB::statement('ALTER TABLE product_components DROP CONSTRAINT IF EXISTS product_components_id_product_id_unique');
            DB::statement('ALTER TABLE production_cut_materials DROP CONSTRAINT IF EXISTS production_cut_materials_per_unit_check');
        }

        Schema::table('product_components', function (Blueprint $table): void {
            $table->decimal('quantity', 14, 4)->nullable(false)->change();
        });

        Schema::table('product_components', function (Blueprint $table): void {
            $table->dropColumn('quantity_mode');
        });

        Schema::table('production_cut_materials', function (Blueprint $table): void {
            $table->decimal('quantity_per_unit', 20, 8)->nullable(false)->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE production_cut_materials ADD CONSTRAINT production_cut_materials_per_unit_check CHECK (quantity_per_unit > 0)');
        }
    }

    private function createTables(): void
    {
        Schema::create('product_component_quantities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_component_id')->constrained('product_components')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamps();

            $table->unique(['product_component_id', 'colour_id', 'size_id'], 'product_component_quantities_cell_unique');
            // The size lock asks "does this product have a cell in this size?".
            $table->index(['product_id', 'size_id'], 'product_component_quantities_product_size_index');
        });

        Schema::create('production_cut_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('production_cut_id')->constrained('production_cuts')->cascadeOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->unsignedInteger('units');
            $table->unsignedInteger('units_returned')->default(0);
            $table->timestamps();

            $table->unique(['production_cut_id', 'colour_id', 'size_id'], 'production_cut_variants_cell_unique');
        });

        Schema::create('production_cut_variant_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('production_cut_variant_id')->constrained('production_cut_variants', 'id', 'production_cut_variant_materials_variant_foreign')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->decimal('quantity_per_unit', 20, 8);
            $table->timestamps();

            $table->unique(['production_cut_variant_id', 'material_id'], 'production_cut_variant_materials_cell_unique');
        });
    }

    private function addPostgresKeys(): void
    {
        // The line's quantity is null exactly when the line is by colour and size, and > 0 otherwise. Any other mode makes the condition false.
        DB::statement("ALTER TABLE product_components ADD CONSTRAINT product_components_quantity_mode_check CHECK ((quantity_mode = 'same' AND quantity IS NOT NULL AND quantity > 0) OR (quantity_mode = 'by_variant' AND quantity IS NULL))");
        DB::statement('ALTER TABLE production_cut_materials ADD CONSTRAINT production_cut_materials_per_unit_check CHECK (quantity_per_unit IS NULL OR quantity_per_unit > 0)');

        DB::statement('ALTER TABLE product_components ADD CONSTRAINT product_components_workshop_id_id_unique UNIQUE (workshop_id, id)');
        DB::statement('ALTER TABLE product_components ADD CONSTRAINT product_components_id_product_id_unique UNIQUE (id, product_id)');
        DB::statement('ALTER TABLE production_cut_variants ADD CONSTRAINT production_cut_variants_workshop_id_id_unique UNIQUE (workshop_id, id)');

        foreach (self::COMPOSITE_KEYS as $constraint => [$table, $columns, $parent, $parentColumns]) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$columns}) REFERENCES {$parent} ({$parentColumns})");
        }

        foreach (self::CHECKS as $constraint => [$table, $condition]) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK ({$condition})");
        }
    }

    /** A by-colour-and-size line has no quantity to go back to, and a cut with variant rows has no per-unit amount of its own. */
    private function refuseWhatCannotGoBack(): void
    {
        $lines = DB::table('product_components')->where('quantity_mode', 'by_variant')->count();

        if ($lines > 0) {
            throw new RuntimeException("Quantities per colour and size can't be removed: {$lines} composition line(s) are By colour and size, with no single quantity to go back to. Make them Same for every variant, or delete them, first.");
        }

        $cuts = DB::table('production_cut_materials')->whereNull('quantity_per_unit')->count();

        if ($cuts > 0) {
            throw new RuntimeException("Quantities per colour and size can't be removed: {$cuts} material(s) of production cuts were taken by colour and size, with no single amount per unit to go back to.");
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
