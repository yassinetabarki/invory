<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-14-a (ARCHITECTURE §4, plan §D.1 / §D.4): the recorded sales.
 *
 * A sale never touches material stock (decision Q8). The product FK restricts: products are soft-deleted, and a hard delete of one
 * that has sales is refused. `user_id` is who recorded the sale (null once that user is deleted, or for seeded demo sales).
 * The two indexes serve the sales log (newest first, `sold_on` then `id`) and the per-product figures.
 *
 * PostgreSQL only: CHECK constraints that mirror the validation rules (quantity 1 to 1 000 000, unit price >= 0). SQLite relies
 * on validation. Written for the parity run, which could not be executed when this was written (Docker was down).
 */
return new class extends Migration
{
    /** @var array<string, string> constraint name => condition */
    private const CHECKS = [
        'sales_quantity_check' => 'quantity BETWEEN 1 AND 1000000',
        'sales_unit_price_check' => 'unit_price >= 0',
    ];

    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->date('sold_on');
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // The log, newest first.
            $table->index(['sold_on', 'id']);
            // The sales of one product over a period.
            $table->index(['product_id', 'sold_on']);
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (self::CHECKS as $name => $condition) {
                DB::statement("ALTER TABLE sales ADD CONSTRAINT {$name} CHECK ({$condition})");
            }
        }
    }

    public function down(): void
    {
        // The CHECK constraints go with the table.
        Schema::dropIfExists('sales');
    }
};
