<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of INV-1 (AC-3, AC-6, AC-7): a colour added in Settings, chosen on a product, seen on its page
|--------------------------------------------------------------------------
|
| A signed-in user adds "Stone wash" (STW, #B7C3CF) in Settings › Colours, opens Edit product for the Denim Jacket (White
| since INV-1), chooses Stone wash in the picker, saves, and finds it in the Colours line under the product's badges. Runs on
| the throwaway database of RefreshDatabase, like the other browser tests.
*/

it('adds a colour in Settings, chooses it on a product and finds it in the Colours line of the product page', function () {
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

    GoldenPathStep::run('settings: add Stone wash', function () use ($page, $browser) {
        $page->navigate(route('settings.colours.index', absolute: false));
        $browser->getByRole('button', ['name' => 'Add colour', 'exact' => true])->click();

        $dialog = $browser->getByRole('dialog', ['name' => 'Add colour', 'exact' => true]);
        $dialog->getByRole('textbox', ['name' => 'Name', 'exact' => true])->fill('Stone wash');
        $dialog->getByRole('textbox', ['name' => 'Code', 'exact' => true])->fill('STW');
        $dialog->getByRole('textbox', ['name' => 'Swatch', 'exact' => true])->fill('#B7C3CF');
        $dialog->getByRole('button', ['name' => 'Save colour', 'exact' => true])->click();

        $page->assertSee('Colour created.')->assertSee('Stone wash');
    }, $page);

    GoldenPathStep::run('edit product: choose Stone wash and save', function () use ($page, $browser) {
        $jacket = Product::query()->where('sku', 'PRD-JKT-001')->firstOrFail();

        $page->navigate(route('products.edit', $jacket, absolute: false));
        $page->assertSee('Edit Denim Jacket');

        $colours = $browser->getByRole('group', ['name' => 'Colours', 'exact' => true]);
        // The jacket was recorded before colours: it has the workshop's first colour.
        $colours->getByRole('button', ['name' => 'Remove White', 'exact' => true]);
        $colours->getByRole('button', ['name' => 'Add colour', 'exact' => true])->click();

        $picker = $browser->getByRole('dialog', ['name' => 'Choose colours', 'exact' => true]);
        $picker->getByRole('option', ['name' => 'Stone wash, STW', 'exact' => true])->click();
        $picker->getByRole('button', ['name' => 'Done', 'exact' => true])->click();

        $colours->getByRole('button', ['name' => 'Remove Stone wash', 'exact' => true]);
        $browser->getByRole('button', ['name' => 'Save product'])->first()->click();

        $page->assertSee('Product updated.');
    }, $page);

    GoldenPathStep::run('product page: the Colours line', function () use ($page) {
        // Stone wash is listed after White, whatever the order they were picked in.
        $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('header span.sr-only'))
            .map((el) => el.textContent.replace(/\s+/g, ' ').trim())
            .find((text) => text.startsWith('Colours:')) ?? 'no Colours line'
        JS, 'Colours: White, Stone wash');
        $page->assertSee('Stone wash');
        $page->assertNoJavaScriptErrors();
    }, $page);
});
