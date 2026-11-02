<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * INV-4-a (ADR-0021): the clients of a workshop, and the client of every order.
 *
 * A client is never removed (no soft deletes). Her phone is kept twice: `phone` as the screens show it ("22 345 678") and `phone_key`,
 * its digits with the country code ("21622345678"), unique in the workshop, so no two clients share a phone however it was typed.
 * PostgreSQL only: `unique(workshop_id, id)` for the composite key from `orders`.
 *
 * `orders.client_id` is nullable only for the orders this migration puts away: every order that exists now was recorded before
 * clients and is test data (the human's decision, 2026-11-02), so it is soft-deleted here, which leaves its lines, its cuts, the
 * materials, their history, sales and finished stock as they are. PostgreSQL also gets the composite key (`workshop_id`,
 * `client_id`) → clients and the CHECK that an order still shown has a client. `orders.client` stays: it keeps the client's name.
 * `down()` drops what `up()` added; the orders it put away stay put away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('phone', 32);
            $table->string('phone_key', 16);
            $table->string('city', 60);
            $table->string('source', 16)->nullable();
            $table->string('source_other', 40)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workshop_id', 'phone_key'], 'clients_workshop_phone_key_unique');
            $table->index(['workshop_id', 'name', 'id'], 'clients_workshop_name_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE clients ADD CONSTRAINT clients_workshop_id_id_unique UNIQUE (workshop_id, id)');
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('workshop_id')->constrained('clients')->restrictOnDelete();
        });

        // Every order recorded so far has no client: put it away (the human's decision, 2026-11-02).
        DB::table('orders')->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_workshop_client_foreign FOREIGN KEY (workshop_id, client_id) REFERENCES clients (workshop_id, id)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_client_shown_check CHECK (client_id IS NOT NULL OR deleted_at IS NOT NULL)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_client_shown_check');
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_workshop_client_foreign');
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::dropIfExists('clients');
    }
};
