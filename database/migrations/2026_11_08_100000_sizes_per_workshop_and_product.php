<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-18-c (ADR-0028, supersedes ADR-0015): each workshop has its own list of sizes and each product its own chosen sizes. Nothing moves:
 * every product keeps exactly the sizes it has, and stock, restocks, barcodes (numbers and names), order lines, batches and work tickets keep
 * pointing at the same sizes.
 *
 * `up()`, in this order:
 * 1. `sizes` gains `workshop_id` and `is_one_size`; its two unique keys (list + label, list + position) go.
 * 2. Every workshop gets 19 sizes, positions 10, 20 … 190: XS … XXXL, 36 … 46, One size (flagged). The LOWEST workshop id adopts the
 *    rows that exist (ids kept); every other workshop gets new rows and its records in the six tables that point at a size are moved onto
 *    them, one mapping per workshop by list code and label. With no workshop the old rows are deleted.
 * 3. `product_sizes`: every product, soft-deleted ones too, gets its category's list, mapped to its workshop's rows.
 *    `product_category_sizes`: each workshop suggests Apparel's and Footwear's lists by category code; any other category has no row
 *    (no row = the workshop's One size).
 * 4. `order_lines.size_list_id` becomes `order_lines.by_size` (true where the list had several sizes).
 * 5. `product_categories.size_list_id`, `sizes.size_list_id` and the table `size_lists` go.
 * 6. Keys: unique per workshop on `lower(label)` (an expression index, the same statement on SQLite and PostgreSQL), at most one
 *    `is_one_size` row per workshop (a partial unique index); PostgreSQL only: composite keys (`workshop_id`, `size_id`) → `sizes
 *    (workshop_id, id)` on the six tables and the two new ones, so the database refuses a size of another workshop (R4).
 *
 * `down()` (ADR-0028 §4.6) refuses, naming the size, when a size isn't one of the 19 starting names (added or renamed). Otherwise it
 * brings the three lists back from the lowest workshop's rows, moves every other workshop's records onto those rows and deletes their
 * copies, and drops every product's own choice (a product falls back to its category's list). `stock_movements.sizes` (the history of
 * cuts) holds labels and is never rewritten.
 *
 * SQLite (the test engine) rebuilds a table for `dropForeign` / `change` / a new foreign key, and dropping a table that others point at
 * fires those keys. `PRAGMA defer_foreign_keys` (F-59) lets the rebuilt table take its place first. The partial name index of product
 * categories is dropped before and created after each rebuild (a rebuild forgets the WHERE of a partial index, F-32), and the new
 * indexes of `sizes` are made last for the same reason. It imports no app class: query builder and Schema only.
 */
return new class extends Migration
{
    /** Positions 10, 20 … 190 follow this order. [old list code, label]; "One size" is the flagged one. */
    private const SIZES = [
        ['s_to_xl', 'XS'], ['s_to_xl', 'S'], ['s_to_xl', 'M'], ['s_to_xl', 'L'], ['s_to_xl', 'XL'], ['s_to_xl', 'XXL'], ['s_to_xl', 'XXXL'],
        ['eu_36_to_46', '36'], ['eu_36_to_46', '37'], ['eu_36_to_46', '38'], ['eu_36_to_46', '39'], ['eu_36_to_46', '40'], ['eu_36_to_46', '41'],
        ['eu_36_to_46', '42'], ['eu_36_to_46', '43'], ['eu_36_to_46', '44'], ['eu_36_to_46', '45'], ['eu_36_to_46', '46'],
        ['one_size', 'One size'],
    ];

    /** The three lists as `down()` restores them: code => name (the names after 2026_11_02: "XS to XXXL"). */
    private const LISTS = ['s_to_xl' => 'XS to XXXL', 'eu_36_to_46' => '36 to 46', 'one_size' => 'One size'];

    /** Apparel and Footwear suggest their old list: category code => list code. */
    private const SUGGESTIONS = ['apparel' => 's_to_xl', 'footwear' => 'eu_36_to_46'];

    /** The six tables whose rows point at `sizes.id` (verified by `size_id` in database/migrations). */
    private const REFERENCING = ['product_size_stocks', 'order_line_sizes', 'barcodes', 'barcode_names', 'product_restock_lines', 'production_batches'];

    /** Tables with the composite key (workshop_id, size_id) on PostgreSQL. */
    private const WORKSHOP_KEYED = [...self::REFERENCING, 'product_sizes', 'product_category_sizes'];

    private const BARCODE_NAMES_KEY = 'barcode_names_barcode_colour_size_foreign';

    public function up(): void
    {
        $this->deferForeignKeysOnSqlite();

        /** @var list<int> $severalSizes the lists the old order lines counted by size */
        $severalSizes = DB::table('sizes')->select('size_list_id')->groupBy('size_list_id')->havingRaw('count(*) > 1')->pluck('size_list_id')->map(fn (mixed $id): int => (int) $id)->all();

        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropUnique(['size_list_id', 'label']);
            $table->dropUnique(['size_list_id', 'position']);
        });

        // SQLite rebuilds `sizes` for the key (the deferral above); the column is NOT NULL again once every row has a workshop.
        Schema::table('sizes', function (Blueprint $table): void {
            $table->foreignId('workshop_id')->nullable()->constrained('workshops')->cascadeOnDelete();
            $table->boolean('is_one_size')->default(false);
        });

        $this->giveEveryWorkshopItsSizes();
        $this->createProductSizes();
        $this->giveEveryProductItsSizes();
        $this->countLinesBySize($severalSizes);
        $this->dropTheListsOfLinesAndCategories();
        $this->createCategorySizes();
        $this->suggestApparelAndFootwear();
        $this->dropTheListsOfSizes();
        $this->finishSizes();
    }

    public function down(): void
    {
        $this->refuseWhenASizeWasChanged();
        $this->deferForeignKeysOnSqlite();

        // The two new tables go first, with their keys: on PostgreSQL their composite keys point at `sizes`.
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_category_sizes');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE barcode_names DROP CONSTRAINT IF EXISTS '.self::BARCODE_NAMES_KEY);

            foreach (self::REFERENCING as $table) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_workshop_size_foreign");
            }

            DB::statement('ALTER TABLE sizes DROP CONSTRAINT IF EXISTS sizes_workshop_id_id_unique');
        }

        DB::statement('DROP INDEX IF EXISTS sizes_workshop_label_unique');
        DB::statement('DROP INDEX IF EXISTS sizes_one_size_unique');

        $this->createTheLists();
        $this->giveSizesTheirListsBack();
        $this->moveEveryWorkshopBackOntoTheLowestOne();
        $this->giveCategoriesTheirListsBack();
        $this->giveLinesTheirListsBack();
        $this->finishOldSizes();
    }

    // ------------------------------------------------------------------ up

    /** One mapping per workshop, by list code and label: the lowest workshop adopts the existing rows, the others get copies. */
    private function giveEveryWorkshopItsSizes(): void
    {
        $workshops = DB::table('workshops')->orderBy('id')->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        if ($workshops === []) {
            DB::table('sizes')->delete();

            return;
        }

        $lists = DB::table('size_lists')->pluck('id', 'code');
        $existing = DB::table('sizes')->join('size_lists', 'size_lists.id', '=', 'sizes.size_list_id')
            ->get(['sizes.id', 'size_lists.code', 'sizes.label'])
            ->mapWithKeys(fn (object $row): array => ["{$row->code}|{$row->label}" => (int) $row->id])->all();
        $now = now();

        if (DB::getDriverName() === 'pgsql') {
            // The composite key names a size by id AND colour: it must not see a barcode between two statements.
            DB::statement('ALTER TABLE barcode_names DROP CONSTRAINT IF EXISTS '.self::BARCODE_NAMES_KEY);
        }

        foreach ($workshops as $index => $workshopId) {
            $moved = [];

            foreach (self::SIZES as $at => [$code, $label]) {
                $values = ['workshop_id' => $workshopId, 'position' => ($at + 1) * 10, 'is_one_size' => $code === 'one_size', 'updated_at' => $now];
                $old = $existing["{$code}|{$label}"] ?? null;

                if ($index === 0 && $old !== null) {
                    DB::table('sizes')->where('id', $old)->update($values);

                    continue;
                }

                $id = (int) DB::table('sizes')->insertGetId([...$values, 'size_list_id' => $lists[$code], 'label' => $label, 'created_at' => $now]);

                if ($old !== null) {
                    $moved[$old] = $id;
                }
            }

            $this->moveRecords($workshopId, $moved);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE barcode_names ADD CONSTRAINT '.self::BARCODE_NAMES_KEY.' FOREIGN KEY (workshop_id, barcode_id, colour_id, size_id) REFERENCES barcodes (workshop_id, id, colour_id, size_id) ON DELETE RESTRICT');
        }
    }

    /**
     * The records of a workshop onto its own rows.
     *
     * @param  array<int, int>  $rows  old size id => the workshop's own row
     */
    private function moveRecords(int $workshopId, array $rows): void
    {
        foreach (self::REFERENCING as $table) {
            foreach ($rows as $old => $new) {
                DB::table($table)->where('workshop_id', $workshopId)->where('size_id', $old)->update(['size_id' => $new]);
            }
        }
    }

    private function createProductSizes(): void
    {
        Schema::create('product_sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();

            $table->unique(['product_id', 'size_id']);
            $table->index('size_id');
        });
    }

    /** After the categories are rebuilt (SQLite): a rebuild would delete these rows, which hang from the category (ON DELETE CASCADE). */
    private function createCategorySizes(): void
    {
        Schema::create('product_category_sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();

            $table->unique(['workshop_id', 'product_category_id', 'size_id']);
            $table->index('size_id');
        });
    }

    /** Every product (`DB::table` ignores the soft-delete scope) gets the rows of its workshop that its category's list had. */
    private function giveEveryProductItsSizes(): void
    {
        DB::table('product_sizes')->insertUsing(
            ['workshop_id', 'product_id', 'size_id'],
            DB::table('products')
                ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
                ->join('sizes', 'sizes.size_list_id', '=', 'product_categories.size_list_id')
                ->whereColumn('sizes.workshop_id', 'products.workshop_id')
                ->select('products.workshop_id', 'products.id', 'sizes.id'),
        );
    }

    /** Apparel and Footwear by CODE (a renamed or archived category keeps its list); no row for any other category. */
    private function suggestApparelAndFootwear(): void
    {
        foreach (self::SUGGESTIONS as $categoryCode => $listCode) {
            DB::table('product_category_sizes')->insertUsing(
                ['workshop_id', 'product_category_id', 'size_id'],
                DB::table('sizes')
                    ->crossJoin('product_categories')
                    ->where('product_categories.code', $categoryCode)
                    ->whereIn('sizes.size_list_id', DB::table('size_lists')->where('code', $listCode)->select('id'))
                    ->whereNotNull('sizes.workshop_id')
                    ->select('sizes.workshop_id', 'product_categories.id', 'sizes.id'),
            );
        }
    }

    /** @param  list<int>  $severalSizes */
    private function countLinesBySize(array $severalSizes): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->boolean('by_size')->default(false);
        });

        if ($severalSizes !== []) {
            DB::table('order_lines')->whereIn('size_list_id', $severalSizes)->update(['by_size' => true]);
        }
    }

    /** One statement group each: SQLite rebuilds the table for `dropForeign`, and refuses to drop a column that is still keyed. */
    private function dropTheListsOfLinesAndCategories(): void
    {
        $this->keepingTheRowsUnderOrderLines(function (): void {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->dropForeign(['size_list_id']);
            });

            Schema::table('order_lines', function (Blueprint $table): void {
                $table->dropIndex(['size_list_id']);
            });

            Schema::table('order_lines', function (Blueprint $table): void {
                $table->dropColumn('size_list_id');
            });
        });

        $this->dropCategoryNameIndex();

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropForeign(['size_list_id']);
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropIndex(['size_list_id']);
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropColumn('size_list_id');
        });

        $this->createCategoryNameIndex();
    }

    /** Last of the lists to go: the suggestions above read them. */
    private function dropTheListsOfSizes(): void
    {
        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropForeign(['size_list_id']);
        });

        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropColumn('size_list_id');
        });

        Schema::dropIfExists('size_lists');
    }

    private function finishSizes(): void
    {
        // Rebuilds `sizes` on SQLite (carrying the key to workshops over), so the indexes below come after it.
        Schema::table('sizes', function (Blueprint $table): void {
            $table->unsignedBigInteger('workshop_id')->nullable(false)->change();
        });

        Schema::table('sizes', function (Blueprint $table): void {
            $table->index(['workshop_id', 'position']);
        });

        // The same statements on both engines (as the F-32 name indexes): `lower()` for the name, a bare boolean for the partial key.
        DB::statement('CREATE UNIQUE INDEX sizes_workshop_label_unique ON sizes (workshop_id, lower(label))');
        DB::statement('CREATE UNIQUE INDEX sizes_one_size_unique ON sizes (workshop_id) WHERE is_one_size');

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE sizes ADD CONSTRAINT sizes_workshop_id_id_unique UNIQUE (workshop_id, id)');

        foreach (self::WORKSHOP_KEYED as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_workshop_size_foreign FOREIGN KEY (workshop_id, size_id) REFERENCES sizes (workshop_id, id)");
        }

        DB::statement('ALTER TABLE product_sizes ADD CONSTRAINT product_sizes_workshop_product_foreign FOREIGN KEY (workshop_id, product_id) REFERENCES products (workshop_id, id)');
    }

    // ---------------------------------------------------------------- down

    /** Only the 19 starting names go back to three lists: a size added or renamed since can't be told which list it belongs to. */
    private function refuseWhenASizeWasChanged(): void
    {
        $names = array_map(fn (array $size): string => $size[1], self::SIZES);
        $size = DB::table('sizes')->whereNotIn('label', $names)->orderBy('workshop_id')->orderBy('id')->first(['label', 'workshop_id']);

        if ($size !== null) {
            throw new RuntimeException("Sizes can't go back to the three lists: workshop {$size->workshop_id} has the size \"{$size->label}\", which isn't one of the 19 starting sizes. Remove or rename it first.");
        }
    }

    private function createTheLists(): void
    {
        Schema::create('size_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 64);
            $table->timestamps();
        });

        $now = now();

        DB::table('size_lists')->insert(array_map(
            fn (string $code, string $name): array => ['code' => $code, 'name' => $name, 'created_at' => $now, 'updated_at' => $now],
            array_keys(self::LISTS),
            self::LISTS,
        ));
    }

    /** The lowest workshop's rows become the three lists again, with their old positions (1 … n in each list). */
    private function giveSizesTheirListsBack(): void
    {
        Schema::table('sizes', function (Blueprint $table): void {
            $table->foreignId('size_list_id')->nullable()->constrained('size_lists')->restrictOnDelete();
        });

        $lowest = DB::table('workshops')->orderBy('id')->value('id');

        if ($lowest === null) {
            return; // No workshop, no size: `finishOldSizes()` makes the 19 rows once `workshop_id` is gone.
        }

        $lists = DB::table('size_lists')->pluck('id', 'code');
        $now = now();

        foreach ($this->oldPositions() as [$code, $label, $position]) {
            DB::table('sizes')->where('workshop_id', $lowest)->where('label', $label)
                ->update(['size_list_id' => $lists[$code], 'position' => $position, 'updated_at' => $now]);
        }
    }

    /**
     * The 19 sizes with their position inside their old list (1 … n).
     *
     * @return list<array{string, string, int}>
     */
    private function oldPositions(): array
    {
        $counts = [];
        $rows = [];

        foreach (self::SIZES as [$code, $label]) {
            $rows[] = [$code, $label, $counts[$code] = ($counts[$code] ?? 0) + 1];
        }

        return $rows;
    }

    /** Every other workshop's records onto the lowest workshop's rows (by label), then their copies go. */
    private function moveEveryWorkshopBackOntoTheLowestOne(): void
    {
        $workshops = DB::table('workshops')->orderBy('id')->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        if (count($workshops) < 2) {
            return;
        }

        $keep = DB::table('sizes')->where('workshop_id', $workshops[0])->pluck('id', 'label');

        foreach (array_slice($workshops, 1) as $workshopId) {
            foreach (DB::table('sizes')->where('workshop_id', $workshopId)->get(['id', 'label']) as $own) {
                if (! isset($keep[$own->label])) {
                    continue;
                }

                foreach (self::REFERENCING as $table) {
                    DB::table($table)->where('workshop_id', $workshopId)->where('size_id', $own->id)->update(['size_id' => $keep[$own->label]]);
                }
            }

            DB::table('sizes')->where('workshop_id', $workshopId)->delete();
        }
    }

    /** By category code: Apparel and Footwear their lists, everything else (a category added in Settings too) "One size". */
    private function giveCategoriesTheirListsBack(): void
    {
        $this->dropCategoryNameIndex();

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->foreignId('size_list_id')->nullable()->constrained('size_lists')->restrictOnDelete();
        });

        foreach (self::SUGGESTIONS as $categoryCode => $listCode) {
            DB::table('product_categories')->where('code', $categoryCode)
                ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = '{$listCode}')")]);
        }

        DB::table('product_categories')->whereNotIn('code', array_keys(self::SUGGESTIONS))
            ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = 'one_size')")]);

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('size_list_id')->nullable(false)->change();
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->index('size_list_id');
        });

        $this->createCategoryNameIndex();
    }

    /**
     * A line counted by size goes back to the list its rows are in (the line's own sizes: its product's category may have changed since),
     * the others to "One size". A by-size line with no row, which can't exist, takes its product's category list.
     */
    private function giveLinesTheirListsBack(): void
    {
        $this->keepingTheRowsUnderOrderLines(function (): void {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->foreignId('size_list_id')->nullable()->constrained('size_lists')->restrictOnDelete();
                $table->index('size_list_id');
            });
        });

        DB::table('order_lines')->where('by_size', true)->update(['size_list_id' => DB::raw(
            '(select sizes.size_list_id from order_line_sizes join sizes on sizes.id = order_line_sizes.size_id'
            .' where order_line_sizes.order_line_id = order_lines.id order by order_line_sizes.id limit 1)'
        )]);
        DB::table('order_lines')->where('by_size', true)->whereNull('size_list_id')->update(['size_list_id' => DB::raw(
            '(select product_categories.size_list_id from products join product_categories on product_categories.id = products.category_id'
            .' where products.id = order_lines.product_id)'
        )]);
        DB::table('order_lines')->whereNull('size_list_id')
            ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = 'one_size')")]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_lines ALTER COLUMN size_list_id SET NOT NULL');
        }

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn('by_size');
        });
    }

    private function finishOldSizes(): void
    {
        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropForeign(['workshop_id']);
        });

        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropIndex(['workshop_id', 'position']);
        });

        Schema::table('sizes', function (Blueprint $table): void {
            $table->dropColumn(['workshop_id', 'is_one_size']);
        });

        if (DB::table('workshops')->doesntExist()) {
            $lists = DB::table('size_lists')->pluck('id', 'code');
            $now = now();

            foreach ($this->oldPositions() as [$code, $label, $position]) {
                DB::table('sizes')->insert(['size_list_id' => $lists[$code], 'label' => $label, 'position' => $position, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        Schema::table('sizes', function (Blueprint $table): void {
            $table->unsignedBigInteger('size_list_id')->nullable(false)->change();
        });

        Schema::table('sizes', function (Blueprint $table): void {
            $table->unique(['size_list_id', 'label']);
            $table->unique(['size_list_id', 'position']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE barcode_names ADD CONSTRAINT '.self::BARCODE_NAMES_KEY.' FOREIGN KEY (workshop_id, barcode_id, colour_id, size_id) REFERENCES barcodes (workshop_id, id, colour_id, size_id) ON DELETE RESTRICT');
        }
    }

    // -------------------------------------------------------------- shared

    /** F-59: SQLite checks keys at the commit instead of at each statement, so a rebuilt parent table can take its place first. */
    private function deferForeignKeysOnSqlite(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        }
    }

    /**
     * SQLite rebuilds `order_lines` by dropping it, and dropping it deletes (ON DELETE CASCADE, which the deferral above doesn't hold back)
     * the rows that hang from it: its size rows, its cuts and their materials. They are copied aside first and put back after. Parents first
     * when putting back, children first when clearing what the cascade left. PostgreSQL changes the table in place.
     */
    private function keepingTheRowsUnderOrderLines(Closure $change): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $change();

            return;
        }

        $tables = array_values(array_filter(['order_line_sizes', 'order_line_cuts', 'order_line_cut_materials'], fn (string $table): bool => Schema::hasTable($table)));

        foreach ($tables as $table) {
            DB::statement("CREATE TEMP TABLE keep_{$table} AS SELECT * FROM {$table}");
        }

        $change();

        foreach (array_reverse($tables) as $table) {
            DB::statement("DELETE FROM {$table}");
        }

        foreach ($tables as $table) {
            DB::statement("INSERT INTO {$table} SELECT * FROM keep_{$table}");
            DB::statement("DROP TABLE keep_{$table}");
        }
    }

    // The F-32 index: an active category name is unique ignoring case. The exact SQL of 2026_10_09_200000, same on both engines.
    private function createCategoryNameIndex(): void
    {
        DB::statement('CREATE UNIQUE INDEX product_categories_active_name_unique ON product_categories (lower(name)) WHERE archived_at IS NULL');
    }

    private function dropCategoryNameIndex(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_categories_active_name_unique');
    }
};
