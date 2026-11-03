<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-13-a (ADR-0025, amends ADR-0016): an order line counts its units per colour AND size. A row now says "this line, in this colour,
 * in this size: N ordered, so many in cutting, ready to ship and shipped".
 *
 * Every row that exists gets its line's product's first colour (position, name, id), as the stock rows did in INV-2 (2026_10_31_100000),
 * so no unit is lost and no count moves. A product with lines and no colour (INV-1 made that impossible) is first linked to its
 * workshop's first colour. Then `colour_id` becomes NOT NULL and the unique key of a row grows from (line, size) to (line, colour, size).
 *
 * The data steps use `DB::table` only and import no app class: the migration must keep working whatever the models become.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->foreignId('colour_id')->nullable()->constrained('colours')->restrictOnDelete();
            $table->index('colour_id');
        });

        $this->fillFirstColour();

        // SQLite rebuilds the table here; its indexes and foreign keys are carried over by the framework (the migration test checks them).
        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->unsignedBigInteger('colour_id')->nullable(false)->change();
        });

        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->dropUnique(['order_line_id', 'size_id']);
            $table->unique(['order_line_id', 'colour_id', 'size_id']);
        });
    }

    public function down(): void
    {
        $this->addColoursBackTogether();

        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->dropUnique(['order_line_id', 'colour_id', 'size_id']);
            $table->dropIndex(['colour_id']);
        });

        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->dropForeign(['colour_id']);
        });

        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->dropColumn('colour_id');
        });

        Schema::table('order_line_sizes', function (Blueprint $table): void {
            $table->unique(['order_line_id', 'size_id']);
        });
    }

    /**
     * Every row takes the first colour of its line's product (position, name, id). A product with rows and no colour is linked to the
     * first colour of its own workshop first.
     */
    private function fillFirstColour(): void
    {
        $productOfLine = [];

        foreach (DB::table('order_lines')->whereIn('id', DB::table('order_line_sizes')->select('order_line_id'))->get(['id', 'product_id']) as $line) {
            $productOfLine[(int) $line->id] = (int) $line->product_id;
        }

        if ($productOfLine === []) {
            return;
        }

        $productIds = array_values(array_unique($productOfLine));
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

        // `DB::table` ignores the soft-delete scope: a deleted product's lines are filled too (their products still exist).
        foreach (DB::table('products')->whereIn('id', array_diff($productIds, array_keys($firstOfProduct)))->get(['id', 'workshop_id']) as $product) {
            $colourId = $firstOfWorkshop[(int) $product->workshop_id] ?? null;

            if ($colourId === null) {
                continue; // The workshop has no colour at all: the NOT NULL below stops the migration instead of losing the units.
            }

            DB::table('product_colours')->insert(['workshop_id' => $product->workshop_id, 'product_id' => $product->id, 'colour_id' => $colourId]);
            $firstOfProduct[(int) $product->id] = $colourId;
        }

        $linesByColour = [];

        foreach ($productOfLine as $lineId => $productId) {
            if (isset($firstOfProduct[$productId])) {
                $linesByColour[$firstOfProduct[$productId]][] = $lineId;
            }
        }

        foreach ($linesByColour as $colourId => $ids) {
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table('order_line_sizes')->whereIn('order_line_id', $chunk)->update(['colour_id' => $colourId]);
            }
        }
    }

    /** One row per line and size again: the colours' units and counts are added up into the oldest row of each, the others go. */
    private function addColoursBackTogether(): void
    {
        $groups = DB::table('order_line_sizes')
            ->select('order_line_id', 'size_id', DB::raw('MIN(id) as keep'))
            ->selectRaw('SUM(quantity) as quantity, SUM(in_cutting) as in_cutting, SUM(ready_to_ship) as ready_to_ship, SUM(shipped) as shipped')
            ->groupBy('order_line_id', 'size_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::table('order_line_sizes')
                ->where('order_line_id', $group->order_line_id)->where('size_id', $group->size_id)->where('id', '<>', $group->keep)
                ->delete();
            DB::table('order_line_sizes')->where('id', $group->keep)->update([
                'quantity' => (int) $group->quantity,
                'in_cutting' => (int) $group->in_cutting,
                'ready_to_ship' => (int) $group->ready_to_ship,
                'shipped' => (int) $group->shipped,
            ]);
        }
    }
};
