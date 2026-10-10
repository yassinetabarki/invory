<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-16-BE (ARCHITECTURE §4, ADR-0010): the append-only stock ledger of a material.
 *
 * `materials.quantity_on_hand` stays the authoritative cached balance; every change to it goes through an Action that appends a
 * row here, so the balance can be rebuilt and checked (`invory:stock:verify`).
 *
 * Backfill: a material that exists today gets one `created` movement whose opening stock is its CURRENT balance and whose price is
 * its current unit cost (the real history is unknown), dated by the day the material was created and credited to its creator. So
 * the ledger balances on an existing database, soft-deleted materials included (their stock is still stored). One portable
 * `INSERT ... SELECT`: no value goes through PHP, so no decimal can be rounded on the way (SQLite hands decimals back as floats).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('type', 16);
            $table->date('occurred_on');
            $table->decimal('quantity', 14, 4)->nullable();
            $table->decimal('quantity_before', 14, 4)->nullable();
            $table->decimal('quantity_after', 14, 4)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('unit_cost_before', 12, 4)->nullable();
            $table->decimal('unit_cost_after', 12, 4)->nullable();
            $table->string('reference', 64)->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Material history, newest first.
            $table->index(['material_id', 'occurred_on', 'id']);
        });

        DB::table('stock_movements')->insertUsing(
            ['material_id', 'type', 'occurred_on', 'quantity', 'quantity_after', 'unit_price', 'user_id', 'created_at', 'updated_at'],
            DB::table('materials')
                ->selectRaw("id, 'created', date(created_at), quantity_on_hand, quantity_on_hand, unit_cost, created_by, created_at, created_at")
                ->orderBy('id'),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
