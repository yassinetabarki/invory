<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of INV-3 (AC-2, AC-3, AC-5, AC-6): a restock of two products, found in the list, opened and undone
|--------------------------------------------------------------------------
|
| A signed-in user opens New restock, adds the Denim Jacket and the Leather Tote through Add product, types 4 in the jacket's first
| field and 2 in the tote's Camel, saves ("6 units restocked."), opens the restock from the Restocks list and undoes it. Runs on the
| throwaway database of RefreshDatabase, like the other browser tests.
*/

it('records a restock of two products, opens it from the Restocks list and undoes it', function () {
    GoldenPathStep::run('database: throwaway', function () {
        expect(DB::connection()->getDatabaseName())->toBe(':memory:');
    });

    $this->seed(DemoCatalogSeeder::class);
    $user = User::factory()->create();
    $tote = Product::query()->where('sku', 'PRD-TOT-004')->firstOrFail();
    $tote->colours()->syncWithoutDetaching([demoColour('CAM')->id => ['workshop_id' => $tote->workshop_id]]);

    $page = visit('/login');
    $browser = $page->page();

    GoldenPathStep::run('sign in', function () use ($page, $browser, $user) {
        $browser->getByLabel('Email address', true)->fill($user->email);
        $browser->getByLabel('Password', true)->fill('password');
        $browser->getByRole('button', ['name' => 'Log in', 'exact' => true])->click();
        $page->assertPathIs('/dashboard');
    }, $page);

    GoldenPathStep::run('new restock: two products, six units', function () use ($page, $browser) {
        $page->navigate(route('restocks.create', absolute: false));
        $page->assertSee('Add the products that were made or arrived, or scan their labels.');

        foreach (['Denim Jacket', 'Leather Tote'] as $name) {
            $browser->getByRole('button', ['name' => 'Add product', 'exact' => true])->click();
            $browser->getByPlaceholder('Search products')->fill($name);
            $browser->getByRole('option', ['name' => $name])->first()->click();
        }

        $browser->getByRole('region', ['name' => 'Denim Jacket', 'exact' => true])->getByRole('textbox')->first()->fill('4');
        $browser->getByRole('region', ['name' => 'Leather Tote', 'exact' => true])->getByLabel('Camel', true)->fill('2');
        $browser->getByRole('button', ['name' => 'Restock 6 units', 'exact' => true])->click();

        $page->assertSee('6 units restocked.')->assertPathIs('/restocks');
    }, $page);

    GoldenPathStep::run('restocks: open it and undo it', function () use ($page, $browser) {
        $browser->getByRole('table', ['name' => 'Restocks', 'exact' => true])->getByRole('button', ['name' => 'Open restock of'])->first()->click();

        $panel = $browser->getByRole('dialog');
        $panel->getByRole('button', ['name' => 'Undo restock', 'exact' => true])->click();

        $confirm = $browser->getByRole('alertdialog', ['name' => 'Undo this restock?', 'exact' => true]);
        $confirm->getByRole('button', ['name' => 'Undo restock', 'exact' => true])->click();

        $page->assertSee('Restock undone.');
        expect(ProductRestock::query()->sole()->undone_at)->not->toBeNull();
    }, $page);
});
