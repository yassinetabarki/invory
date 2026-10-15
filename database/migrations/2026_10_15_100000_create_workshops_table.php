<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0013 (S-44-a): the business account is a workshop, and every user belongs to exactly one. Accounts that exist today join ONE
 * workshop, which will hold everything recorded so far (S-44-b moves the records in). A database without users gets no workshop.
 *
 * `workshop_id` is added nullable, filled, then made NOT NULL. SQLite rebuilds `users` for the foreign key and for `change()`,
 * and re-creates the table's indexes from introspection (the unique email index survives; the migration's test checks it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('workshop_id')->nullable()->constrained('workshops')->restrictOnDelete();
        });

        if (DB::table('users')->exists()) {
            $workshopId = DB::table('workshops')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

            DB::table('users')->update(['workshop_id' => $workshopId]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('workshop_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['workshop_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('workshop_id');
        });

        Schema::dropIfExists('workshops');
    }
};
