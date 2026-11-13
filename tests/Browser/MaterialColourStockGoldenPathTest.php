<?php

declare(strict_types=1);

use App\Actions\Products\CreateProduct;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Support\GoldenPathStep;

/*
|--------------------------------------------------------------------------
| Golden path of INV-21 (AC-7, AC-14, AC-19): stock per colour, restocked in one receipt, then used by a batch
|--------------------------------------------------------------------------
|
| A signed-in user adds Denim 12oz with Stock per colour (Indigo 250 m, Black 40 m, Light wash 0 m, the same cost for all), restocks
| Indigo 100 and Black 20 in one receipt from the material page ("Restocked Denim 12oz: …"), finds Indigo 350 m, Black 60 m and 410 m in all, then
| starts a batch Indigo M 6 · Black S 5 of a Denim Jacket that uses 1.8 m of it per unit, 10 % waste (1.98 m a unit): Indigo takes
| 6 × 1.98 = 11.88 m (338.12 m left), Black 5 × 1.98 = 9.9 m (50.1 m left). Run at desktop width and at 375 px, where the material
| page, the Restock sheet and the Start batch window must not scroll sideways. At 1280 px the composition table of the Denim Jacket (three
| colours on a per-colour material, one colour per line) must not scroll sideways either. Runs on the throwaway database of RefreshDatabase, like
| the other browser tests.
*/

/**
 * The composition table of the product page, at 1280 px: it must not scroll sideways, whatever lines the colours of a per-colour
 * material make in its Stock on hand cell. The layout needs a moment, so it re-reads for up to two seconds. Returns "ok", or the problem.
 */
function compositionTableFits(): string
{
    return <<<'JS'
    (async () => {
        const measure = () => {
            if (window.innerWidth !== 1280) return 'the window is ' + window.innerWidth + ' px wide';

            const caption = Array.from(document.querySelectorAll('table caption')).find((el) => el.textContent.trim() === 'Composition');
            if (!caption) return 'no composition table';

            const table = caption.closest('table');
            const box = table.parentElement;
            const lines = table.querySelectorAll('tbody li').length;

            if (lines < 3) return 'the colours are not listed one per line: ' + lines + ' lines';
            if (box.scrollWidth > box.clientWidth + 1) return 'the composition table scrolls sideways: ' + box.scrollWidth + ' > ' + box.clientWidth;
            if (table.scrollWidth > box.clientWidth + 1) return 'the composition table is wider than its box: ' + table.scrollWidth + ' > ' + box.clientWidth;

            return 'ok';
        };

        let result = measure();
        for (let attempt = 0; attempt < 20 && result !== 'ok'; attempt++) {
            await new Promise((resolve) => setTimeout(resolve, 100));
            result = measure();
        }

        return result;
    })()
    JS;
}

/**
 * AC-19: 375 px wide, one column, nothing scrolls sideways: the page, and every open window or sheet. The layout needs a moment after a
 * resize or an opening, so it re-reads for up to two seconds. Returns "ok", or the problems found.
 */
function colourStockFitsPhone(): string
{
    return <<<'JS'
    (async () => {
        const measure = () => {
            const issues = [];
            const root = document.documentElement;

            if (window.innerWidth !== 375) issues.push('the window is ' + window.innerWidth + ' px wide');
            if (root.scrollWidth > window.innerWidth) issues.push('the page scrolls sideways: ' + root.scrollWidth + ' > ' + window.innerWidth);
            if (document.body.scrollWidth > window.innerWidth) issues.push('the body scrolls sideways: ' + document.body.scrollWidth + ' > ' + window.innerWidth);

            document.querySelectorAll('[role="dialog"]').forEach((el) => {
                if (el.scrollWidth > el.clientWidth + 1) {
                    issues.push('a window scrolls sideways: ' + (el.getAttribute('aria-label') ?? el.textContent.trim().slice(0, 30)) + ' ' + el.scrollWidth + ' > ' + el.clientWidth);
                }
            });

            return issues;
        };

        let issues = measure();
        for (let attempt = 0; attempt < 20 && issues.length > 0; attempt++) {
            await new Promise((resolve) => setTimeout(resolve, 100));
            issues = measure();
        }

        return issues.length === 0 ? 'ok' : issues.join(' | ');
    })()
    JS;
}

it('adds a per-colour material, restocks two colours in one receipt and starts a batch that takes from each colour', function (int $width, int $height) {
    GoldenPathStep::run('database: throwaway', function () {
        expect(DB::connection()->getDatabaseName())->toBe(':memory:');
    });

    $user = User::factory()->create();
    $phone = $width === 375;

    $page = visit('/login');
    $browser = $page->page();

    GoldenPathStep::run('sign in', function () use ($page, $browser, $user, $width, $height) {
        $page->resize($width, $height);
        $browser->getByLabel('Email address', true)->fill($user->email);
        $browser->getByLabel('Password', true)->fill('password');
        $browser->getByRole('button', ['name' => 'Log in', 'exact' => true])->click();
        $page->assertPathIs('/dashboard');
    }, $page);

    GoldenPathStep::run('materials: add Denim 12oz, stock per colour', function () use ($page, $browser) {
        $page->navigate(route('materials.create', absolute: false));
        $browser->getByRole('textbox', ['name' => 'SKU', 'exact' => true])->fill('MAT-DEN-PC');
        $browser->getByRole('textbox', ['name' => 'Name', 'exact' => true])->fill('Denim 12oz');

        $browser->getByRole('combobox', ['name' => 'Category', 'exact' => true])->click();
        $browser->getByRole('option', ['name' => 'Fabric', 'exact' => true])->click();
        $browser->getByRole('combobox', ['name' => 'Unit of measure', 'exact' => true])->click();
        $browser->getByRole('option', ['name' => 'Metre (m)', 'exact' => true])->click();

        $browser->getByRole('radio', ['name' => 'Stock per colour', 'exact' => true])->check();
        $page->assertSee('No colours yet. Add the colours you buy this material in.');
        $browser->getByRole('textbox', ['name' => 'Unit cost', 'exact' => true])->fill('8.500');

        $browser->getByRole('button', ['name' => 'Add colour', 'exact' => true])->click();
        $picker = $browser->getByRole('dialog', ['name' => 'Choose colours', 'exact' => true]);
        $picker->getByRole('option', ['name' => 'Indigo, IND', 'exact' => true])->click();
        $picker->getByRole('option', ['name' => 'Black, BLK', 'exact' => true])->click();
        $picker->getByRole('option', ['name' => 'Light wash, LWH', 'exact' => true])->click();
        $picker->getByRole('button', ['name' => 'Done', 'exact' => true])->click();

        // The rows follow Settings › Colours (Black before Indigo), whatever the order they were picked in.
        $browser->getByRole('textbox', ['name' => 'Quantity in stock Indigo', 'exact' => true])->fill('250');
        $browser->getByRole('textbox', ['name' => 'Quantity in stock Black', 'exact' => true])->fill('40');
        $browser->getByRole('textbox', ['name' => 'Quantity in stock Light wash', 'exact' => true])->fill('0');

        $browser->getByRole('button', ['name' => 'Save material'])->first()->click();
        $page->assertSee('Material created.')->assertSee('Stock by colour');
    }, $page);

    $denim = Material::query()->where('sku', 'MAT-DEN-PC')->firstOrFail();
    $stockOf = fn (): array => $denim->colours()->with('colour')->get()
        ->mapWithKeys(fn ($row): array => [$row->colour->name => $row->quantity_on_hand])->all();

    GoldenPathStep::run('materials: stock per colour as typed', function () use ($page, $stockOf, $denim) {
        expect($stockOf())->toEqual(['Indigo' => '250.0000', 'Black' => '40.0000', 'Light wash' => '0.0000'])
            ->and($denim->refresh()->quantity_on_hand)->toBe('290.0000');
        $page->assertSee('290 m');
    }, $page);

    if ($phone) {
        GoldenPathStep::run('phone: the material page has one column and no sideways scroll', function () use ($page) {
            $page->assertScript(colourStockFitsPhone(), 'ok');
        }, $page);
    }

    GoldenPathStep::run('restock: Indigo 100 and Black 20 in one receipt', function () use ($page, $browser, $phone) {
        $browser->getByRole('button', ['name' => 'Restock', 'exact' => true])->first()->click();

        $sheet = $browser->getByRole('dialog', ['name' => 'Restock Denim 12oz', 'exact' => true]);
        $page->assertSee('In stock now: 290 m in 3 colours · unit cost');

        $sheet->getByLabel('Unit price paid', true)->fill('8.500');
        $sheet->getByRole('group', ['name' => 'Indigo', 'exact' => true])->getByLabel('Quantity received', true)->fill('100');
        $sheet->getByRole('group', ['name' => 'Black', 'exact' => true])->getByLabel('Quantity received', true)->fill('20');
        $page->assertSee('2 colours in this receipt');

        if ($phone) {
            $page->assertScript(colourStockFitsPhone(), 'ok');
        }

        $sheet->getByRole('button', ['name' => 'Add to stock', 'exact' => true])->click();
    }, $page);

    GoldenPathStep::run('restock: the toast and the new figures', function () use ($page, $stockOf, $denim) {
        // The server lists the colours in Settings › Colours order: Black comes before Indigo.
        $page->assertSee('Restocked Denim 12oz: Black +20 m, Indigo +100 m.');
        expect($stockOf())->toEqual(['Indigo' => '350.0000', 'Black' => '60.0000', 'Light wash' => '0.0000'])
            ->and($denim->refresh()->quantity_on_hand)->toBe('410.0000');
        $page->assertSee('350 m')->assertSee('60 m')->assertSee('410 m');
    }, $page);

    // The Denim Jacket (Indigo, Black and Light wash, sizes from Apparel) draws 1.8 m of Denim 12oz a unit with 10 % waste, from each variant's own colour.
    GoldenPathStep::run('product: the Denim Jacket uses Denim 12oz', function () use ($denim, $user) {
        app(CreateProduct::class)->handle([
            'sku' => 'E2E-JKT', 'name' => 'Denim Jacket', 'category' => 'apparel', 'status' => 'active',
            'labor_cost' => '12.0000', 'overhead_cost' => '3.5000', 'selling_price' => null,
            'colours' => [demoColour('IND')->id, demoColour('BLK')->id, demoColour('LWH')->id],
            'components' => [['material_id' => $denim->id, 'quantity' => '1.8000', 'waste_percent' => '10.00']],
        ], $user);
    }, $page);

    if (! $phone) {
        GoldenPathStep::run('product: the composition table has no sideways scroll, one colour per line', function () use ($page) {
            $jacket = Product::query()->where('sku', 'E2E-JKT')->firstOrFail();
            $page->navigate(route('products.show', $jacket, absolute: false));
            $page->assertSee('Composition');
            $page->assertScript(compositionTableFits(), 'ok');
        }, $page);
    }

    GoldenPathStep::run('progress: start a batch, Indigo M 6 and Black S 5', function () use ($page, $browser, $phone) {
        $page->navigate(route('progress.index', absolute: false));
        $browser->getByRole('button', ['name' => 'Start batch', 'exact' => true])->first()->click();

        $window = $browser->getByRole('dialog', ['name' => 'Start batch', 'exact' => true]);
        $window->getByRole('combobox', ['name' => 'Product'])->click();
        $browser->getByPlaceholder('Search products')->fill('Denim Jacket');
        $browser->getByRole('option', ['name' => 'Denim Jacket'])->first()->click();

        $window->getByRole('textbox', ['name' => 'Indigo M', 'exact' => true])->fill('6');
        $window->getByRole('textbox', ['name' => 'Black S', 'exact' => true])->fill('5');
        $page->assertSee('Total 11 units · 2 batches');
        // One row per material and colour: 6 × 1.98 m of Indigo, 5 × 1.98 m of Black.
        $page->assertSee('Materials for 11 units')->assertSee('11.88 m')->assertSee('9.9 m');

        if ($phone) {
            $page->assertScript(colourStockFitsPhone(), 'ok');
        }

        $window->getByRole('button', ['name' => 'Start batch', 'exact' => true])->click();
        $page->assertSee('2 batches started')->assertSee('11 units of Denim Jacket started. Materials taken out of stock.');
    }, $page);

    GoldenPathStep::run('materials: each colour gave its own stock', function () use ($page, $browser, $stockOf, $denim) {
        // 350 − 11.88 = 338.12 m of Indigo, 60 − 9.9 = 50.1 m of Black, 388.22 m in all.
        expect($stockOf())->toEqual(['Indigo' => '338.1200', 'Black' => '50.1000', 'Light wash' => '0.0000'])
            ->and($denim->refresh()->quantity_on_hand)->toBe('388.2200');

        $browser->getByRole('button', ['name' => 'Done', 'exact' => true])->click();
        $page->navigate(route('materials.show', $denim, absolute: false));
        $page->assertSee('338.12 m')->assertSee('50.1 m')->assertSee('388.22 m');
        $page->assertNoJavaScriptErrors();
    }, $page);
})->with([
    'desktop' => [1280, 900],
    'phone, 375 px' => [375, 812],
]);
