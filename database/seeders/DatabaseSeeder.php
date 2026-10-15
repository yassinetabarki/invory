<?php

namespace Database\Seeders;

use App\Actions\Workshops\CreateWorkshop;
use App\Models\User;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The demo workshop, for a new, empty database (`php artisan db:seed`). Four steps, in this order:
 *
 * 1. the test user (`test@example.com` / `password`), created only when missing, in a new workshop of its own;
 * 2. {@see DemoCatalogSeeder}: the 12 materials, then the 7 products on them;
 * 3. {@see DemoStockHistorySeeder}: the stock history of those materials, credited to the test user;
 * 4. {@see DemoSalesSeeder}: the 60 demo sales, only when that workshop has no sales yet.
 *
 * Steps 2 to 4 run inside `CurrentWorkshop::run()` with the workshop of the test user, so everything they create belongs to it.
 *
 * Each demo step is idempotent: running this again creates nothing twice, and a demo product you deleted stays deleted.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(CreateWorkshop $createWorkshop): void
    {
        // Found by email. A new test user gets a new workshop of its own, so running this again creates neither twice.
        // `email_verified_at` and `workshop_id` are not mass-assignable, so this runs unguarded. `password` is hashed by the cast.
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::unguarded(fn (): User => User::query()->create([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'workshop_id' => $createWorkshop->handle()->id,
            ]));
        }

        // The demo data belongs to the workshop of the test user (the demo seeders would find it themselves; this says so).
        CurrentWorkshop::run(User::query()->where('email', 'test@example.com')->firstOrFail()->workshop_id, function (): void {
            $this->call(DemoCatalogSeeder::class);

            $this->call(DemoStockHistorySeeder::class);

            $this->call(DemoSalesSeeder::class);
        });
    }
}
