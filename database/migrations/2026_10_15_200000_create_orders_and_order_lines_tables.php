<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-48-a: customer orders, and the product lines of each order.
 *
 * Both tables carry the workshop (ADR-0013); a line takes its order's. Orders are soft-deleted so that later steps can keep pointing
 * at the line a history entry came from. A line keeps its product when that product is archived or soft-deleted, so the product
 * foreign key restricts. Recording an order never touches materials, stock or sales.
 *
 * PostgreSQL only: `unique(workshop_id, id)` on orders and composite foreign keys that make the database refuse a line whose workshop
 * differs from its order's or its product's (R4), plus a CHECK that mirrors the quantity rule. SQLite relies on validation.
 * `products` already has `unique(workshop_id, id)` (2026_10_15_100100).
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> constraint => [column of order_lines, parent table], each on (workshop_id, <column>) */
    private const COMPOSITE_KEYS = [
        'order_lines_workshop_order_foreign' => ['order_id', 'orders'],
        'order_lines_workshop_product_foreign' => ['product_id', 'products'],
    ];

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('client', 100);
            $table->date('ordered_on');
            $table->string('note', 1000)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // The list of a workshop, newest first.
            $table->index(['workshop_id', 'ordered_on', 'id']);
        });

        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
            $table->index('product_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_workshop_id_id_unique UNIQUE (workshop_id, id)');

            foreach (self::COMPOSITE_KEYS as $constraint => [$column, $parent]) {
                DB::statement("ALTER TABLE order_lines ADD CONSTRAINT {$constraint} FOREIGN KEY (workshop_id, {$column}) REFERENCES {$parent} (workshop_id, id)");
            }

            DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_quantity_check CHECK (quantity BETWEEN 1 AND 1000000)');
        }
    }

    public function down(): void
    {
        // The lines go first: on PostgreSQL their composite keys point at orders. The constraints go with their tables.
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
