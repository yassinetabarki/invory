<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-15-g (ADR-0024 decision 7): how a workshop's labels and work tickets print, one row per workshop, made on first save.
 * `ticket_size`: `a4_8` (an A4 sheet of 8 tickets, the default) or `label_100x60` (one per 100 × 60 mm label). INV-9 adds the label
 * size and the selling price to this row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_label_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->unique()->constrained('workshops')->restrictOnDelete();
            $table->string('ticket_size', 16)->default('a4_8');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE workshop_label_settings ADD CONSTRAINT workshop_label_settings_ticket_size_check CHECK (ticket_size IN ('a4_8', 'label_100x60'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_label_settings');
    }
};
