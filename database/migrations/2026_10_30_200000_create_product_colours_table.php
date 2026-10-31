<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-1-e (ADR-0018): the colours of a product. A product has at least one (the request enforces it, not the table).
 *
 * Products are soft-deleted, so the cascade on `product_id` only fires on a hard delete; a colour is never removed, so `restrict`
 * on `colour_id` is a safety net. PostgreSQL only: composite foreign keys (`workshop_id`, `product_id`) and (`workshop_id`,
 * `colour_id`), so the database itself refuses a colour of another workshop (R4; `products` has `unique(workshop_id, id)` since
 * 2026_10_15_100100 and `colours` since 2026_10_30_100000).
 *
 * Every product that exists, soft-deleted ones too, gets its own workshop's first colour in list order (White). It imports no app class.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('colour_id')->constrained('colours')->restrictOnDelete();

            $table->unique(['product_id', 'colour_id']);
            $table->index('colour_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_colours ADD CONSTRAINT product_colours_workshop_product_foreign FOREIGN KEY (workshop_id, product_id) REFERENCES products (workshop_id, id)');
            DB::statement('ALTER TABLE product_colours ADD CONSTRAINT product_colours_workshop_colour_foreign FOREIGN KEY (workshop_id, colour_id) REFERENCES colours (workshop_id, id)');
        }

        $this->whiteForEveryProduct();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colours');
    }

    /** One link per product, to the first colour (position, name, id) of its own workshop. A workshop with no colour links nothing. */
    private function whiteForEveryProduct(): void
    {
        $first = [];

        foreach (DB::table('colours')->orderBy('workshop_id')->orderBy('position')->orderBy('name')->orderBy('id')->get(['id', 'workshop_id']) as $colour) {
            $first[$colour->workshop_id] ??= (int) $colour->id;
        }

        foreach ($first as $workshopId => $colourId) {
            // `DB::table` ignores the soft-delete scope: deleted products get their colour too.
            DB::table('products')->where('workshop_id', $workshopId)->orderBy('id')->chunkById(500, function ($products) use ($workshopId, $colourId): void {
                DB::table('product_colours')->insert(
                    $products->map(fn (object $product): array => [
                        'workshop_id' => $workshopId,
                        'product_id' => $product->id,
                        'colour_id' => $colourId,
                    ])->all(),
                );
            });
        }
    }
};
