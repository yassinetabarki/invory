<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-14-a (ADR-0026, supersedes the stages of ADR-0016 and the order parts of ADR-0014): an order no longer runs production. Each order
 * line and each of its colour and size rows counts **Delivered** and **Cancelled**; To deliver is whatever is left of the quantity.
 *
 * Every order is mapped (the human's answers of 2026-11-03): Shipped becomes Delivered (the shelf doesn't change); To start, In cutting
 * and Ready to ship become To deliver. The Ready-to-ship units physically exist, so they are first put on the shelf: one restock per
 * workshop, dated the day of the change, Reference "Orders", Note "Ready to ship before INV-14", one line per product, colour and size.
 * A shelf figure that would pass 1,000,000 stops the migration, naming its product, colour and size, rather than lose units.
 *
 * Then `in_cutting`, `ready_to_ship` and `shipped` go. PostgreSQL only: CHECKs that both counts are ≥ 0 and add up to at most the
 * quantity. `down()` brings the stage columns back with Shipped = Delivered; the restock stays (it is real stock by then).
 *
 * The data steps use `DB::table` only and import no app class: the migration must keep working whatever the models become.
 */
return new class extends Migration
{
    private const TABLES = ['order_lines', 'order_line_sizes'];

    private const STAGES = ['in_cutting', 'ready_to_ship', 'shipped'];

    private const MAX_UNITS = 1000000;

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedInteger('delivered')->default(0);
                $blueprint->unsignedInteger('cancelled')->default(0);
            });

            DB::table($table)->update(['delivered' => DB::raw('shipped')]);
        }

        $this->putReadyToShipOnTheShelf();

        foreach (self::TABLES as $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_stage_counts_total_check");
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_stage_counts_check");
            }

            // One column per statement: SQLite rebuilds the table for each.
            foreach (self::STAGES as $column) {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->dropColumn($column);
                });
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_delivery_counts_check CHECK (delivered >= 0 AND cancelled >= 0)");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_delivery_counts_total_check CHECK (delivered + cancelled <= quantity)");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_delivery_counts_total_check");
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_delivery_counts_check");
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                foreach (self::STAGES as $column) {
                    $blueprint->unsignedInteger($column)->default(0);
                }
            });

            DB::table($table)->update(['shipped' => DB::raw('delivered')]);

            foreach (['delivered', 'cancelled'] as $column) {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->dropColumn($column);
                });
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_stage_counts_check CHECK (in_cutting >= 0 AND ready_to_ship >= 0 AND shipped >= 0)");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_stage_counts_total_check CHECK (in_cutting + ready_to_ship + shipped <= quantity)");
            }
        }
    }

    /**
     * One restock "Orders" per workshop with the Ready-to-ship units of its orders (deleted orders left out: nothing waits for them),
     * added to the shelf per product, colour and size.
     */
    private function putReadyToShipOnTheShelf(): void
    {
        $rows = DB::table('order_line_sizes')
            ->join('order_lines', 'order_lines.id', '=', 'order_line_sizes.order_line_id')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->whereNull('orders.deleted_at')
            ->where('order_line_sizes.ready_to_ship', '>', 0)
            ->groupBy('orders.workshop_id', 'order_lines.product_id', 'order_line_sizes.colour_id', 'order_line_sizes.size_id')
            ->orderBy('orders.workshop_id')->orderBy('order_lines.product_id')->orderBy('order_line_sizes.colour_id')->orderBy('order_line_sizes.size_id')
            ->selectRaw('orders.workshop_id, order_lines.product_id, order_line_sizes.colour_id, order_line_sizes.size_id, SUM(order_line_sizes.ready_to_ship) as units')
            ->get();

        $now = now();

        foreach ($rows->groupBy('workshop_id') as $workshopId => $lines) {
            $restockId = DB::table('product_restocks')->insertGetId([
                'workshop_id' => $workshopId,
                'restocked_on' => $now->toDateString(),
                'reference' => 'Orders',
                'note' => 'Ready to ship before INV-14',
                'units' => (int) $lines->sum('units'),
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($lines->values() as $position => $line) {
                $units = (int) $line->units;

                DB::table('product_restock_lines')->insert([
                    'workshop_id' => $workshopId, 'product_restock_id' => $restockId, 'product_id' => $line->product_id,
                    'colour_id' => $line->colour_id, 'size_id' => $line->size_id, 'position' => $position, 'units' => $units,
                    'created_at' => $now, 'updated_at' => $now,
                ]);

                $this->addToShelf((int) $workshopId, (int) $line->product_id, (int) $line->colour_id, (int) $line->size_id, $units);
            }
        }
    }

    private function addToShelf(int $workshopId, int $productId, int $colourId, int $sizeId, int $units): void
    {
        // The colour of a shelf row must be one of the product's (the PostgreSQL key of INV-2).
        $linked = DB::table('product_colours')->where('product_id', $productId)->where('colour_id', $colourId)->exists();

        if (! $linked) {
            DB::table('product_colours')->insert(['workshop_id' => $workshopId, 'product_id' => $productId, 'colour_id' => $colourId]);
        }

        $row = DB::table('product_size_stocks')->where(['product_id' => $productId, 'colour_id' => $colourId, 'size_id' => $sizeId])->first();
        $total = ($row === null ? 0 : (int) $row->units) + $units;

        if ($total > self::MAX_UNITS) {
            $product = DB::table('products')->where('id', $productId)->value('name');
            $colour = DB::table('colours')->where('id', $colourId)->value('name');
            $size = DB::table('sizes')->where('id', $sizeId)->value('label');

            throw new RuntimeException("{$product} {$colour} {$size} would hold {$total} units on the shelf, more than 1,000,000: lower its stock first.");
        }

        if ($row === null) {
            DB::table('product_size_stocks')->insert([
                'workshop_id' => $workshopId, 'product_id' => $productId, 'colour_id' => $colourId, 'size_id' => $sizeId,
                'units' => $units, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            DB::table('product_size_stocks')->where('id', $row->id)->update(['units' => $total, 'updated_at' => now()]);
        }
    }
};
