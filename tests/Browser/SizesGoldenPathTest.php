<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of INV-18 (AC-5, AC-6): a size added in Settings, chosen on a product, seen on its page
|--------------------------------------------------------------------------
|
| A signed-in user adds "XXS" at position 15 in Settings › Sizes, opens Edit product for the Denim Jacket (demo data: Apparel,
| so XS to XXXL, one colour: White), takes XS, XXL and XXXL off, puts XXS on, saves, and finds "Sizes XXS, S, M, L, XL" in the badges
| and "1 colour × 5 sizes = 5 variants" in the Sizes & colours card. (The story's example reads 3 colours × 5 sizes = 15 variants:
| the demo jacket has one colour, so the card reads 5.) Runs on the throwaway database of RefreshDatabase, like the other browser tests.
*/

it('adds a size in Settings, chooses it on a product and finds the product\'s own sizes on its page', function () {
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

    GoldenPathStep::run('settings: add XXS at 15', function () use ($page, $browser) {
        $page->navigate(route('settings.sizes.index', absolute: false));
        $browser->getByRole('button', ['name' => 'Add size', 'exact' => true])->click();

        $dialog = $browser->getByRole('dialog', ['name' => 'Add size', 'exact' => true]);
        $dialog->getByLabel('Name', true)->fill('XXS');
        $dialog->getByLabel('Position (optional)', true)->fill('15');
        $dialog->getByRole('button', ['name' => 'Save size', 'exact' => true])->click();

        $page->assertSee('Size created.')->assertSee('XXS');
    }, $page);

    GoldenPathStep::run('edit product: XS, XXL, XXXL off, XXS on', function () use ($page, $browser) {
        $jacket = Product::query()->where('sku', 'PRD-JKT-001')->firstOrFail();

        $page->navigate(route('products.edit', $jacket, absolute: false).'#sizes');
        $page->assertSee('Edit Denim Jacket');

        $sizes = $browser->getByRole('group', ['name' => 'Sizes', 'exact' => true]);
        $page->assertSee('7 sizes chosen');

        foreach (['XS', 'XXL', 'XXXL'] as $off) {
            $sizes->getByRole('button', ['name' => $off, 'exact' => true])->click();
        }

        $sizes->getByRole('button', ['name' => 'XXS', 'exact' => true])->click();
        $page->assertSee('5 sizes chosen');

        $browser->getByRole('button', ['name' => 'Save product'])->first()->click();

        $page->assertSee('Product updated.');
    }, $page);

    GoldenPathStep::run('product page: the badge and the variants line', function () use ($page) {
        $page->assertSee('Sizes XXS, S, M, L, XL');
        $page->assertSee('1 colour × 5 sizes = 5 variants.');
        $page->assertNoJavaScriptErrors();
    }, $page);
});
