<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-15-BE: the path of a material's photo on the public disk (`products.photo_path` exists since S-04a). Nullable: no photo.
 *
 * SQLite rebuilds a table on some column changes and re-creates its indexes from introspection, which does not know the `WHERE`
 * clause of a partial index, so the SKU index is dropped first and created again at the end (as the audit-columns migration does).
 * Both engines run the same code path.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->string('photo_path', 255)->nullable();
        });

        $this->createSkuIndex();
    }

    public function down(): void
    {
        $this->dropSkuIndex();

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
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
