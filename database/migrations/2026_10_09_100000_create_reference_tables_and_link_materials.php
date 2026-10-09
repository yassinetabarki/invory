<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0008: units, material categories and product categories become tables, and `materials` points at them.
 *
 * The defaults below are a frozen snapshot of the former PHP enums (this migration imports no app class, so a later
 * refactor can't rewrite history). They are inserted with `insertOrIgnore` keyed by `code`, so re-running is harmless.
 * To add a default later, write a new migration.
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: string}> code => name, in the order of the former enum (becomes `position`). */
    private const MATERIAL_CATEGORIES = [
        ['fabric', 'Fabric'],
        ['lining', 'Lining'],
        ['interlining', 'Interlining'],
        ['leather', 'Leather'],
        ['thread', 'Thread'],
        ['trim', 'Trim'],
        ['fastener', 'Fastener'],
        ['label', 'Label'],
        ['sole', 'Sole'],
        ['hardware', 'Hardware'],
        ['packaging', 'Packaging'],
        ['other', 'Other'],
    ];

    /** @var list<array{0: string, 1: string}> */
    private const PRODUCT_CATEGORIES = [
        ['apparel', 'Apparel'],
        ['footwear', 'Footwear'],
        ['bag', 'Bag'],
        ['accessory', 'Accessory'],
        ['jewelry', 'Jewelry'],
        ['other', 'Other'],
    ];

    /** @var list<array{0: string, 1: string, 2: string, 3: string|null}> code, name, dimension, factor to the base unit */
    private const UNITS = [
        ['m', 'Metre', 'length', '1'],
        ['cm', 'Centimetre', 'length', '0.01'],
        ['yd', 'Yard', 'length', '0.9144'],
        ['sqm', 'Square metre', 'area', '1'],
        ['sqft', 'Square foot', 'area', '0.09290304'],
        ['kg', 'Kilogram', 'mass', '1'],
        ['g', 'Gram', 'mass', '0.001'],
        ['l', 'Litre', 'volume', '1'],
        ['ml', 'Millilitre', 'volume', '0.001'],
        ['pc', 'Piece', 'count', '1'],
        ['pair', 'Pair', 'count', '2'],
        ['roll', 'Roll', 'package', null],
        ['cone', 'Cone', 'package', null],
    ];

    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 64);
            $table->string('dimension', 16);
            $table->decimal('factor_to_base', 24, 12)->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        foreach (['material_categories', 'product_categories'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name', 64);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        $this->insertDefaults();

        Schema::table('materials', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained('material_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->restrictOnDelete();
        });

        // Portable correlated subqueries (no UPDATE ... FROM, no JOIN: both engines run these as written).
        DB::table('materials')->update([
            'category_id' => DB::raw('(select id from material_categories where material_categories.code = materials.category)'),
            'unit_id' => DB::raw('(select id from units where units.code = materials.unit)'),
        ]);

        $this->assertEveryMaterialIsMapped();

        // SQLite rebuilds the table on a column change or drop, and re-creates its indexes from introspection, which
        // does not know the `WHERE` clause of a partial index. Drop it first and create it again at the end (PostgreSQL
        // does the same, so both engines run one code path).
        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropIndex(['category']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn(['category', 'unit']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->index('category_id');
            $table->index('unit_id');
        });

        $this->createSkuIndex();
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->string('category', 32)->nullable();
            $table->string('unit', 16)->nullable();
        });

        DB::table('materials')->update([
            'category' => DB::raw('(select code from material_categories where material_categories.id = materials.category_id)'),
            'unit' => DB::raw('(select code from units where units.id = materials.unit_id)'),
        ]);

        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['unit_id']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn(['category_id', 'unit_id']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->string('category', 32)->nullable(false)->change();
            $table->string('unit', 16)->nullable(false)->change();
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->index('category');
        });

        $this->createSkuIndex();

        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('material_categories');
        Schema::dropIfExists('units');
    }

    private function insertDefaults(): void
    {
        $now = now();

        foreach ([['material_categories', self::MATERIAL_CATEGORIES], ['product_categories', self::PRODUCT_CATEGORIES]] as [$table, $rows]) {
            DB::table($table)->insertOrIgnore(array_map(
                fn (array $row, int $position): array => [
                    'code' => $row[0],
                    'name' => $row[1],
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $rows,
                array_keys($rows),
            ));
        }

        DB::table('units')->insertOrIgnore(array_map(
            fn (array $row, int $position): array => [
                'code' => $row[0],
                'name' => $row[1],
                'dimension' => $row[2],
                'factor_to_base' => $row[3],
                'is_system' => true,
                'position' => $position,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::UNITS,
            array_keys(self::UNITS),
        ));
    }

    /**
     * Fails loudly (and rolls the data back where the engine can) instead of dropping a code that has no row.
     */
    private function assertEveryMaterialIsMapped(): void
    {
        $unmapped = DB::table('materials')
            ->where(fn ($query) => $query->whereNull('category_id')->orWhereNull('unit_id'))
            ->get(['id', 'category', 'unit']);

        if ($unmapped->isNotEmpty()) {
            $details = $unmapped
                ->map(fn (object $row): string => "#{$row->id} (category '{$row->category}', unit '{$row->unit}')")
                ->implode(', ');

            throw new RuntimeException("Cannot move materials to reference tables, no matching default for: {$details}.");
        }
    }

    // SKU is unique among non-deleted rows only. Same syntax on PostgreSQL and SQLite.
    private function createSkuIndex(): void
    {
        DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (sku) WHERE deleted_at IS NULL');
    }

    private function dropSkuIndex(): void
    {
        DB::statement('DROP INDEX materials_sku_unique');
    }
};
