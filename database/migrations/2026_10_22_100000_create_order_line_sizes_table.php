<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-60-a (ADR-0016): an order line is counted by size.
 *
 * - `order_line_sizes`: one row per size of a line (the quantity and the three stage counts of that size). The line keeps its own
 *   figures as the sum of its rows.
 * - `order_lines.size_list_id`: the size list the line counts by.
 * - `stock_movements.sizes`: the sizes a cutting entry moved (JSON, written from S-61-a).
 *
 * Every line that exists is filled by code (this migration imports no app class, the S-58 pattern): it gets the `one_size` list and one
 * row on that list's only size, holding the line's quantity and its three counts.
 *
 * PostgreSQL only: `order_lines.size_list_id` becomes NOT NULL (SQLite keeps it nullable, so `order_lines`, which two tables point at,
 * is never rebuilt), a composite foreign key that makes the database refuse a row whose workshop differs from its line's (R4), and
 * CHECKs that mirror the line's rules. `order_lines` has `unique(workshop_id, id)` since the cuts migration (2026_10_16_200000).
 */
return new class extends Migration
{
    private const COUNTS = ['in_cutting', 'ready_to_ship', 'shipped'];

    public function up(): void
    {
        Schema::create('order_line_sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('order_line_id')->constrained('order_lines')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            foreach (self::COUNTS as $count) {
                $table->unsignedInteger($count)->default(0);
            }
            $table->timestamps();

            $table->unique(['order_line_id', 'size_id']);
            $table->index('size_id');
        });

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->foreignId('size_list_id')->nullable()->constrained('size_lists')->restrictOnDelete();
            $table->index('size_list_id');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->json('sizes')->nullable();
        });

        $this->fillExistingLines();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_lines ALTER COLUMN size_list_id SET NOT NULL');
            DB::statement('ALTER TABLE order_line_sizes ADD CONSTRAINT order_line_sizes_workshop_line_foreign FOREIGN KEY (workshop_id, order_line_id) REFERENCES order_lines (workshop_id, id)');
            DB::statement('ALTER TABLE order_line_sizes ADD CONSTRAINT order_line_sizes_quantity_check CHECK (quantity BETWEEN 1 AND 1000000)');
            DB::statement('ALTER TABLE order_line_sizes ADD CONSTRAINT order_line_sizes_stage_counts_check CHECK (in_cutting >= 0 AND ready_to_ship >= 0 AND shipped >= 0)');
            DB::statement('ALTER TABLE order_line_sizes ADD CONSTRAINT order_line_sizes_stage_counts_total_check CHECK (in_cutting + ready_to_ship + shipped <= quantity)');
        }
    }

    public function down(): void
    {
        // The table goes first, with its constraints: on PostgreSQL its composite key points at order_lines.
        Schema::dropIfExists('order_line_sizes');

        // One statement group each, as in the S-58 migration: SQLite rebuilds `order_lines` for each of them.
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropForeign(['size_list_id']);
        });

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropIndex(['size_list_id']);
        });

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropColumn('size_list_id');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropColumn('sizes');
        });
    }

    /** Every line is "One size": the list, and one row on its only size with the line's quantity and counts. */
    private function fillExistingLines(): void
    {
        $listId = DB::table('size_lists')->where('code', 'one_size')->value('id');
        $sizeId = DB::table('sizes')->where('size_list_id', $listId)->value('id');

        DB::table('order_lines')->update(['size_list_id' => $listId]);

        DB::table('order_line_sizes')->insertUsing(
            ['workshop_id', 'order_line_id', 'size_id', 'quantity', ...self::COUNTS, 'created_at', 'updated_at'],
            DB::table('order_lines')->select('workshop_id', 'id')
                ->selectRaw('? as size_id', [$sizeId])
                ->addSelect(['quantity', ...self::COUNTS, 'created_at', 'updated_at']),
        );
    }
};
