<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-2-a (ADR-0019, amends ADR-0017): the finished units of a product are counted per colour AND size. A row now says "this product,
 * in this colour, in this size, N units", still only while it holds at least 1 unit.
 *
 * Every row that exists gets its product's first colour (position, name, id; White for a product recorded before INV-1), so no unit is
 * lost or counted twice. A row whose product has no colour (INV-1 made that impossible) first links the product to its workshop's
 * first colour, so there is never a "No colour" row. Then `colour_id` becomes NOT NULL and the unique key of a row grows from (product,
 * size) to (product, colour, size).
 *
 * PostgreSQL only: a composite foreign key (`product_id`, `colour_id`) to `product_colours`, so the database itself refuses stock for a
 * colour the product doesn't have (and a colour with stock can't be detached from its product). `product_colours` has
 * `unique(product_id, colour_id)` since 2026_10_30_200000.
 *
 * The data steps use `DB::table` only and import no app class: the migration must keep working whatever the models become.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->foreignId('colour_id')->nullable()->constrained('colours')->restrictOnDelete();
            $table->index('colour_id');
        });

        $this->moveStockIntoFirstColour();

        // SQLite rebuilds the table here; its indexes and foreign keys are carried over by the framework (the migration test checks them).
        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('colour_id')->nullable(false)->change();
        });

        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->dropUnique(['product_id', 'size_id']);
            $table->unique(['product_id', 'colour_id', 'size_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_size_stocks ADD CONSTRAINT product_size_stocks_product_colour_foreign FOREIGN KEY (product_id, colour_id) REFERENCES product_colours (product_id, colour_id)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_size_stocks DROP CONSTRAINT IF EXISTS product_size_stocks_product_colour_foreign');
        }

        $this->addColoursBackTogether();

        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->dropUnique(['product_id', 'colour_id', 'size_id']);
            $table->dropIndex(['colour_id']);
        });

        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->dropForeign(['colour_id']);
        });

        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->dropColumn('colour_id');
        });

        Schema::table('product_size_stocks', function (Blueprint $table): void {
            $table->unique(['product_id', 'size_id']);
        });
    }

    /**
     * Every stock row takes the first colour of its product (position, name, id). A product with stock and no colour is linked to the
     * first colour of its own workshop first.
     */
    private function moveStockIntoFirstColour(): void
    {
        $productIds = DB::table('product_size_stocks')->distinct()->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->all();

        if ($productIds === []) {
            return;
        }

        $firstOfProduct = [];

        foreach (array_chunk($productIds, 500) as $chunk) {
            $links = DB::table('product_colours')
                ->join('colours', 'colours.id', '=', 'product_colours.colour_id')
                ->whereIn('product_colours.product_id', $chunk)
                ->orderBy('product_colours.product_id')->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
                ->get(['product_colours.product_id', 'colours.id as colour_id']);

            foreach ($links as $link) {
                $firstOfProduct[(int) $link->product_id] ??= (int) $link->colour_id;
            }
        }

        $firstOfWorkshop = [];

        foreach (DB::table('colours')->orderBy('workshop_id')->orderBy('position')->orderBy('name')->orderBy('id')->get(['id', 'workshop_id']) as $colour) {
            $firstOfWorkshop[(int) $colour->workshop_id] ??= (int) $colour->id;
        }

        // `DB::table` ignores the soft-delete scope: a deleted product's rows are moved too (their products still exist).
        foreach (DB::table('products')->whereIn('id', array_diff($productIds, array_keys($firstOfProduct)))->get(['id', 'workshop_id']) as $product) {
            $colourId = $firstOfWorkshop[(int) $product->workshop_id] ?? null;

            if ($colourId === null) {
                continue; // The workshop has no colour at all: the NOT NULL below stops the migration instead of losing the stock.
            }

            DB::table('product_colours')->insert(['workshop_id' => $product->workshop_id, 'product_id' => $product->id, 'colour_id' => $colourId]);
            $firstOfProduct[(int) $product->id] = $colourId;
        }

        $productsByColour = [];

        foreach ($firstOfProduct as $productId => $colourId) {
            $productsByColour[$colourId][] = $productId;
        }

        foreach ($productsByColour as $colourId => $ids) {
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table('product_size_stocks')->whereIn('product_id', $chunk)->update(['colour_id' => $colourId]);
            }
        }
    }

    /** One row per product and size again: the colours' units are added up into the oldest row of each, the others go. */
    private function addColoursBackTogether(): void
    {
        $groups = DB::table('product_size_stocks')
            ->select('product_id', 'size_id', DB::raw('SUM(units) as total'), DB::raw('MIN(id) as keep'))
            ->groupBy('product_id', 'size_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::table('product_size_stocks')->where('product_id', $group->product_id)->where('size_id', $group->size_id)->where('id', '<>', $group->keep)->delete();
            DB::table('product_size_stocks')->where('id', $group->keep)->update(['units' => (int) $group->total]);
        }
    }
};
