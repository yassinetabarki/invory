<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of S-48: record an order with two products (AC-2)
|--------------------------------------------------------------------------
|
| A signed-in user with the demo products opens "New order", fills it in and saves. The list opens with the toast and the
| new order on its first row. Runs on the throwaway database of RefreshDatabase, like the other browser test.
*/

it('records an order with two products and finds it first on the Orders list', function () {
    GoldenPathStep::run('database: throwaway', function () {
        expect(DB::connection()->getDatabaseName())->toBe(':memory:');
    });

    $this->seed(DemoCatalogSeeder::class);
    $user = User::factory()->create();

    $page = visit('/login');
    $browser = $page->page();

    GoldenPathStep::run('sign in', function () use ($page, $browser, $user) {
        $browser->getByLabel('Email address', true)->fill($user->email);
        $browser->getByLabel('Password', true)->fill('password');
        $browser->getByRole('button', ['name' => 'Log in', 'exact' => true])->click();
        $page->assertPathIs('/dashboard');
    }, $page);

    GoldenPathStep::run('new order: open the page', function () use ($page, $browser) {
        $page->navigate(route('orders.create', absolute: false));
        $page->assertSee('New order');
        $browser->getByRole('button', ['name' => 'Add product', 'exact' => true])->click();
    }, $page);

    GoldenPathStep::run('new order: fill in the client and two products', function () use ($browser) {
        $browser->getByLabel('Client', true)->fill('Boutique Lina');
        $browser->getByLabel('Note (optional)', true)->fill('Deliver before 30 Oct, in 2 boxes');

        foreach ([[1, 'Denim Jacket · PRD-JKT-001', '50'], [2, 'Denim Jeans 5-pocket · PRD-JNS-005', '30']] as [$number, $option, $quantity]) {
            $line = $browser->getByRole('group', ['name' => "Line {$number}", 'exact' => true]);
            $line->getByRole('combobox', ['name' => 'Product', 'exact' => true])->click();
            $browser->getByRole('option', ['name' => $option, 'exact' => true])->click();
            $line->getByLabel('Quantity', true)->fill($quantity);
        }
    }, $page);

    GoldenPathStep::run('new order: save', function () use ($page, $browser) {
        $browser->getByRole('button', ['name' => 'Save order', 'exact' => true])->click();
        $page->assertPathIs('/orders')->assertSee('Order recorded.');
    }, $page);

    GoldenPathStep::run('orders list: the new order is the first row', function () use ($page) {
        // Client, products, units and note of the first row (the date column depends on the day the test runs).
        $page->assertScript(<<<'JS'
        (() => {
            const row = document.querySelector('table tbody tr');
            return row
                ? Array.from(row.querySelectorAll('td'))
                    .slice(1, 5)
                    .map((cell) => cell.innerText.replace(/[ \t\r\n\f\v]+/g, ' ').trim())
                    .join(' | ')
                : '';
        })()
        JS, 'Boutique Lina | 50 × Denim Jacket 30 × Denim Jeans 5-pocket | 80 | Deliver before 30 Oct, in 2 boxes');
        $page->assertNoJavaScriptErrors();
    }, $page);
});
