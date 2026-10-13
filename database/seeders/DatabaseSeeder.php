<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // The mockup's 12 materials and the 7 demo products on them.
        $this->call(DemoCatalogSeeder::class);

        // The 60 demo sales of those products (only when there are no sales yet).
        $this->call(DemoSalesSeeder::class);
    }
}
