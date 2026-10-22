<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0015 (S-58-a): the fixed size lists, and the link from every product category to one of them.
 *
 * The lists are a frozen snapshot (this migration imports no app class), inserted with `insertOrIgnore` keyed by `code`, so re-running is
 * harmless. They are installation-wide: no `workshop_id`, and no screen edits them. A category is linked by its immutable CODE (apparel,
 * footwear, anything else), never by its name, so a renamed or archived Apparel keeps S, M, L, XL, and a category added before this
 * change is "One size". To add a list later, write a new migration.
 */
return new class extends Migration
{
    /** @var array<string, array{name: string, labels: list<string>}> code => name and size labels in order (the position is the index + 1). */
    private const LISTS = [
        's_to_xl' => ['name' => 'S, M, L, XL', 'labels' => ['S', 'M', 'L', 'XL']],
        'eu_36_to_46' => ['name' => '36 to 46', 'labels' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']],
        'one_size' => ['name' => 'One size', 'labels' => ['One size']],
    ];

    public function up(): void
    {
        Schema::create('size_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 64);
            $table->timestamps();
        });

        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('size_list_id')->constrained('size_lists')->restrictOnDelete();
            $table->string('label', 16);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['size_list_id', 'label']);
            $table->unique(['size_list_id', 'position']);
        });

        $this->insertLists();

        // The partial name index is dropped first and created again at the end: SQLite rebuilds the table on the NOT NULL change and
        // re-creates its indexes from introspection, which forgets the `WHERE` of a partial index (see the F-32 migration).
        $this->dropNameIndex();

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->foreignId('size_list_id')->nullable()->constrained('size_lists')->restrictOnDelete();
        });

        // Portable correlated subqueries, as in the S-21 migration. By code: the name may have been edited since.
        DB::table('product_categories')->where('code', 'apparel')
            ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = 's_to_xl')")]);
        DB::table('product_categories')->where('code', 'footwear')
            ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = 'eu_36_to_46')")]);
        DB::table('product_categories')->whereNotIn('code', ['apparel', 'footwear'])
            ->update(['size_list_id' => DB::raw("(select id from size_lists where size_lists.code = 'one_size')")]);

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('size_list_id')->nullable(false)->change();
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->index('size_list_id');
        });

        $this->createNameIndex();
    }

    public function down(): void
    {
        $this->dropNameIndex();

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropForeign(['size_list_id']);
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropIndex(['size_list_id']);
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropColumn('size_list_id');
        });

        $this->createNameIndex();

        Schema::dropIfExists('sizes');
        Schema::dropIfExists('size_lists');
    }

    private function insertLists(): void
    {
        $now = now();

        DB::table('size_lists')->insertOrIgnore(array_map(
            fn (string $code, array $list): array => ['code' => $code, 'name' => $list['name'], 'created_at' => $now, 'updated_at' => $now],
            array_keys(self::LISTS),
            self::LISTS,
        ));

        foreach (self::LISTS as $code => $list) {
            $listId = DB::table('size_lists')->where('code', $code)->value('id');

            DB::table('sizes')->insertOrIgnore(array_map(
                fn (string $label, int $index): array => [
                    'size_list_id' => $listId,
                    'label' => $label,
                    'position' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $list['labels'],
                array_keys($list['labels']),
            ));
        }
    }

    // The F-32 index: an active category name is unique ignoring case. The exact SQL of 2026_10_09_200000, same on both engines.
    private function createNameIndex(): void
    {
        DB::statement('CREATE UNIQUE INDEX product_categories_active_name_unique ON product_categories (lower(name)) WHERE archived_at IS NULL');
    }

    private function dropNameIndex(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_categories_active_name_unique');
    }
};
