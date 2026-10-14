<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The demo workshop, for a new, empty database (`php artisan db:seed`). Four steps, in this order:
 *
 * 1. the test user (`test@example.com` / `password`), created only when missing;
 * 2. {@see DemoCatalogSeeder}: the 12 materials, then the 7 products on them;
 * 3. {@see DemoStockHistorySeeder}: the stock history of those materials, credited to the test user;
 * 4. {@see DemoSalesSeeder}: the 60 demo sales, only when there are no sales yet.
 *
 * Each demo step is idempotent: running this again creates nothing twice, and a demo product you deleted stays deleted.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // `email_verified_at` is not mass-assignable, so this runs unguarded. `password` is hashed by the model's cast.
        User::unguarded(fn (): User => User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        ));

        $this->call(DemoCatalogSeeder::class);

        $this->call(DemoStockHistorySeeder::class);

        $this->call(DemoSalesSeeder::class);
    }
}
