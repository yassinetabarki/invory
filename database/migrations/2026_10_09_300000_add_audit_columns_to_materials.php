<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0010 audit: who created and who last changed a material (S-04a). Nullable, and the id is nulled when the user is deleted.
 * Rows that exist today stay NULL (nobody knows who made them).
 *
 * SQLite rebuilds a table on some column changes and re-creates its indexes from introspection, which does not know the `WHERE`
 * clause of a partial index, so the SKU index is dropped first and created again at the end (as the S-21 migration does).
 * Both engines run the same code path.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        $this->createSkuIndex();
    }

    public function down(): void
    {
        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        $this->createSkuIndex();
    }

    // SKU is unique among non-deleted rows only. Same syntax on PostgreSQL and SQLite.
    private function createSkuIndex(): void
    {
        DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (sku) WHERE deleted_at IS NULL');
    }

    private function dropSkuIndex(): void
    {
        DB::statement('DROP INDEX IF EXISTS materials_sku_unique');
    }
};
