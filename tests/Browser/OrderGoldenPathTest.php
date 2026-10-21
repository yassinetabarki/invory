<?php

declare(strict_types=1);

use App\Models\Material;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of S-48, S-49 and S-53: record an order, then start cutting (S-48 AC-2, S-49 AC-1 and AC-2, S-53 AC-1 and AC-2)
|--------------------------------------------------------------------------
|
| A signed-in user with the demo products opens "New order", fills it in and saves. The order's own page opens with the
| toast; "Start cutting" shows the materials table, then moves 30 jackets into cutting: the figures and the toast follow
| and the denim's own page shows what is left in stock. The new order is then the first row of the Orders list. Runs on the throwaway database of RefreshDatabase, like the other browser test.
*/

it('records an order with two products, starts cutting 30 jackets and finds it first on the Orders list', function () {
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

    GoldenPathStep::run('new order: save lands on the order page', function () use ($page, $browser) {
        $browser->getByRole('button', ['name' => 'Save order', 'exact' => true])->click();
        $page->assertPathIs(route('orders.show', Order::query()->firstOrFail(), false))
            ->assertSee('Order recorded.')
            ->assertSee('Ordered ')
            ->assertSee('2 products · 80 units')
            ->assertSee('0 of 80 shipped');
    }, $page);

    GoldenPathStep::run('order page: start cutting 30 jackets', function () use ($page, $browser) {
        $jacket = $browser->getByRole('region', ['name' => 'Denim Jacket · PRD-JKT-001 · 50 units', 'exact' => true]);
        $jacket->getByRole('button', ['name' => 'Start cutting', 'exact' => true])->click();

        $dialog = $browser->getByRole('dialog', ['name' => 'Start cutting · Denim Jacket', 'exact' => true]);
        $dialog->getByLabel('How many units?', true)->fill('30');

        // The server works the table out for the number typed; the row waits for its answer (S-53 AC-1).
        $denimRow = $dialog->getByRole('row', ['name' => 'Denim 12oz Indigo']);
        expect(preg_replace('/\s+/', ' ', trim($denimRow->innerText())))->toBe('Denim 12oz Indigo 59.4 m 250 m 190.6 m');

        $dialog->getByRole('button', ['name' => 'Move units', 'exact' => true])->click();

        $page->assertSee('30 units of Denim Jacket moved to In cutting. Materials taken out of stock.');
        $page->assertScript(<<<'JS'
        (() => {
            const jacket = Array.from(document.querySelectorAll('h3'))
                .find((heading) => heading.innerText.startsWith('Denim Jacket'))
                ?.closest('section');
            return jacket
                ? Array.from(jacket.querySelectorAll('ol > li > p'))
                    .map((figure) => figure.innerText.replace(/[ \t\r\n\f\v]+/g, ' ').trim())
                    .join(' | ')
                : '';
        })()
        JS, 'To start 20 units | In cutting 30 units | Ready to ship 0 units | Shipped 0 units');
        $page->assertNoJavaScriptErrors();
    }, $page);

    GoldenPathStep::run('material page: the denim left in stock follows the cut', function () use ($page) {
        $page->navigate(route('materials.show', Material::query()->where('name', 'Denim 12oz Indigo')->firstOrFail(), false));
        $page->assertScript(<<<'JS'
        (() => {
            const label = Array.from(document.querySelectorAll('dt')).find((term) => term.innerText.trim() === 'In stock');
            return label?.nextElementSibling?.innerText.trim() ?? '';
        })()
        JS, '190.6 m');
        $page->assertNoJavaScriptErrors();
    }, $page);

    GoldenPathStep::run('orders list: the new order is the first row', function () use ($page) {
        $page->navigate(route('orders.index', absolute: false));
        // Client (its note under it), products, Ordered, the four stages and Status of the first row (the date column depends on the day the test runs).
        $page->assertScript(<<<'JS'
        (() => {
            const row = document.querySelector('table tbody tr');
            return row
                ? Array.from(row.querySelectorAll('td'))
                    .slice(1, 9)
                    .map((cell) => cell.innerText.replace(/[ \t\r\n\f\v]+/g, ' ').trim())
                    .join(' | ')
                : '';
        })()
        JS, 'Boutique Lina Deliver before 30 Oct, in 2 boxes | 50 × Denim Jacket 30 × Denim Jeans 5-pocket | 80 | 50 | 30 | 0 | 0 | In progress');
        $page->assertNoJavaScriptErrors();
    }, $page);
});
