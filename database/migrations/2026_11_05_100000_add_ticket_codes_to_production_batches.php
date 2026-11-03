<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * INV-15-a (ADR-0024): work tickets and scanning.
 *
 * - `production_batches.ticket_code`: 32 random letters and digits, unique, in the QR code of the batch's work ticket. Made when the
 *   batch is made (a split gets its own). The batches already there get one here. PostgreSQL also refuses a batch without one.
 * - `production_batch_moves.scanned`: the move was made from the Scan page ("Your scans today").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table): void {
            $table->string('ticket_code', 32)->nullable()->after('number');
        });

        // The batches made before INV-15: one code each. Not through the model, so no workshop scope is involved.
        DB::table('production_batches')->whereNull('ticket_code')->orderBy('id')->pluck('id')
            ->each(static fn (int $id): int => DB::table('production_batches')->where('id', $id)->update(['ticket_code' => Str::random(32)]));

        Schema::table('production_batches', function (Blueprint $table): void {
            $table->unique('ticket_code');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE production_batches ALTER COLUMN ticket_code SET NOT NULL');
        }

        Schema::table('production_batch_moves', function (Blueprint $table): void {
            $table->boolean('scanned')->default(false)->after('to_label');
            $table->index(['workshop_id', 'user_id', 'created_at'], 'production_batch_moves_scans_index');
        });
    }

    public function down(): void
    {
        Schema::table('production_batch_moves', function (Blueprint $table): void {
            $table->dropIndex('production_batch_moves_scans_index');
            $table->dropColumn('scanned');
        });

        Schema::table('production_batches', function (Blueprint $table): void {
            $table->dropUnique(['ticket_code']);
            $table->dropColumn('ticket_code');
        });
    }
};
