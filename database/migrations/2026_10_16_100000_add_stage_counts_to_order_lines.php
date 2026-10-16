<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-49-a: how many units of an order line are In cutting, Ready to ship and Shipped. To start is never stored: it is the quantity
 * minus the three, so the four figures always add up to the quantity.
 *
 * PostgreSQL only: CHECKs that refuse a negative count (the unsigned columns of SQLite are not enforced) and counts that add up to
 * more than the quantity. SQLite relies on the Action that moves the units.
 */
return new class extends Migration
{
    private const COUNTS = ['in_cutting', 'ready_to_ship', 'shipped'];

    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            foreach (self::COUNTS as $count) {
                $table->unsignedInteger($count)->default(0);
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_stage_counts_check CHECK (in_cutting >= 0 AND ready_to_ship >= 0 AND shipped >= 0)');
            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_stage_counts_total_check CHECK (in_cutting + ready_to_ship + shipped <= quantity)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_lines DROP CONSTRAINT IF EXISTS order_lines_stage_counts_total_check');
            DB::statement('ALTER TABLE order_lines DROP CONSTRAINT IF EXISTS order_lines_stage_counts_check');
        }

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn(self::COUNTS);
        });
    }
};
