<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\Browser\Support\GoldenPathStep;

/** Money as the app shows it: the currency code, a no-break space (Intl.NumberFormat), the amount. */
function goldenTnd(string $amount): string
{
    return "TND\u{00A0}{$amount}";
}

/**
 * Waits until an element matching $selector shows exactly $expected, or $expected followed by more text (a hint under
 * a figure). Whitespace runs collapse to one space; no-break spaces stay. A failure lists the texts that were there.
 */
function goldenShows(PendingAwaitablePage $page, string $selector, string $expected): void
{
    $selector = json_encode($selector, JSON_THROW_ON_ERROR);
    $quoted = json_encode($expected, JSON_THROW_ON_ERROR);

    $page->assertScript(<<<JS
    (() => {
        const texts = Array.from(document.querySelectorAll({$selector}))
            .map((el) => el.innerText.replace(/[ \\t\\r\\n\\f\\v]+/g, ' ').trim());
        const found = texts.some((text) => text === {$quoted} || text.startsWith({$quoted} + ' '));
        return found ? {$quoted} : texts.join(' || ');
    })()
    JS, $expected);
}

/**
 * AC-2: the pages fill in after the frame (`<Deferred>`), and their placeholders are `role="status"` texts that are
 * visually hidden ("Loading x…", "Checking x…"), which Playwright treats as hidden, so `assertDontSee('Loading')`
 * would pass at once. This waits until no such status is left on the page.
 */
function goldenNoPlaceholders(PendingAwaitablePage $page): void
{
    $page->assertScript(<<<'JS'
    Array.from(document.querySelectorAll('[role="status"]'))
        .map((el) => el.textContent.trim())
        .filter((text) => /^(Loading|Checking) /.test(text))
        .join(' | ')
    JS, '');
}

/**
 * S-73-b: measures the three figure cards of the product page ("Production capacity", "Finished stock", "Unit cost"):
 * the size of their words, where "Update stock" sits, and (mode `wide`) one row of equal cards of at least 200 px or
 * (mode `narrow`) a stack in order without a sideways scroll. The layout needs a moment after a resize, so it re-reads
 * for up to two seconds. Returns "ok", or the problems found.
 */
function goldenHeroCards(string $mode): string
{
    return str_replace('__MODE__', $mode, <<<'JS'
    (async () => {
        const mode = '__MODE__';
        const cards = [['Production capacity', 'Can produce now'], ['Finished stock', 'In stock'], ['Unit cost', 'Unit cost']];
        const measure = () => {
            const issues = [];
            const sections = cards.map(([name]) => document.querySelector('section[aria-label="' + name + '"]'));
            if (sections.some((section) => !section)) {
                return { issues: ['a card is missing'], widths: [] };
            }

            const rects = sections.map((section) => section.getBoundingClientRect());
            const widths = rects.map((rect) => Math.round(rect.width * 10) / 10);

            if (mode === 'wide') {
                if (window.innerWidth !== 1280) issues.push('the window is ' + window.innerWidth + ' px wide');
                if (rects.some((rect) => Math.abs(rect.top - rects[0].top) > 1)) issues.push('the cards do not share one top: ' + rects.map((rect) => rect.top).join(', '));
                if (Math.max(...widths) - Math.min(...widths) > 1) issues.push('the cards are not equal: ' + widths.join(', '));
                if (Math.min(...widths) < 200) issues.push('a card is under 200 px: ' + widths.join(', '));
                if (rects.some((rect, i) => i > 0 && rect.left < rects[i - 1].right - 1)) issues.push('the cards are not left to right in order');
            } else {
                if (rects.some((rect, i) => i > 0 && rect.top < rects[i - 1].bottom - 1)) issues.push('the cards do not stack in order: tops ' + rects.map((rect) => rect.top).join(', '));
                if (document.documentElement.scrollWidth > window.innerWidth) issues.push('the page scrolls sideways: ' + document.documentElement.scrollWidth + ' > ' + window.innerWidth);
            }

            sections.forEach((section, i) => {
                const [name, labelText] = cards[i];
                const label = Array.from(section.querySelectorAll('span')).find((el) => el.textContent.trim() === labelText);
                const figure = section.children[1];
                const size = (el) => (el ? getComputedStyle(el).fontSize : 'missing');
                if (size(figure) !== '36px') issues.push(name + ': the figure is ' + size(figure));
                if (size(label) !== '15px') issues.push(name + ': the label is ' + size(label));
                if (figure && figure.scrollWidth > figure.clientWidth + 1) issues.push(name + ': the figure overflows its card');
            });

            const stock = sections[1];
            const button = Array.from(stock.querySelectorAll('button')).find((el) => el.textContent.trim() === 'Update stock');
            const label = Array.from(stock.querySelectorAll('span')).find((el) => el.textContent.trim() === 'In stock');
            const figure = stock.children[1].getBoundingClientRect();
            const b = button.getBoundingClientRect();
            const l = label.getBoundingClientRect();
            if (!(b.top < l.bottom && b.bottom > l.top)) issues.push('"Update stock" is not on the "In stock" row');
            if (b.bottom > figure.top + 1 && b.top < figure.bottom - 1 && b.right > figure.left && b.left < figure.right) issues.push('"Update stock" covers the units');
            if (b.right > rects[1].right) issues.push('"Update stock" is outside its card');

            return { issues, widths };
        };

        let result = measure();
        for (let attempt = 0; attempt < 20 && result.issues.length > 0; attempt++) {
            await new Promise((resolve) => setTimeout(resolve, 100));
            result = measure();
        }

        return result.issues.length === 0 ? 'ok' : result.issues.join(' | ');
    })()
    JS);
}

it('a new user goes from sign-in to a sale on the dashboard', function () {
    GoldenPathStep::run('database: throwaway', function () {
        expect(DB::connection()->getDatabaseName())->toBe(':memory:');
    });

    $page = visit('/login');
    $browser = $page->page();
    $field = fn (string $name) => $browser->getByRole('textbox', ['name' => $name, 'exact' => true]);
    $choose = function (string $select, string $option) use ($browser): void {
        $browser->getByRole('combobox', ['name' => $select, 'exact' => true])->click();
        $browser->getByRole('option', ['name' => $option, 'exact' => true])->click();
    };
    $open = fn (string $route) => $page->navigate(route($route, absolute: false));

    GoldenPathStep::run('sign in', function () use ($page, $browser) {
        $user = User::factory()->create();
        $browser->getByLabel('Email address', true)->fill($user->email);
        $browser->getByLabel('Password', true)->fill('password');
        $browser->getByRole('button', ['name' => 'Log in', 'exact' => true])->click();
        $page->assertPathIs('/dashboard');
    }, $page);

    $addMaterial = function (string $sku, string $name, string $category, string $unit, string $cost, string $stock) use ($page, $browser, $field, $choose, $open): void {
        $open('materials.create');
        $field('SKU')->fill($sku);
        $field('Name')->fill($name);
        $choose('Category', $category);
        $choose('Unit of measure', $unit);
        $field('Unit cost')->fill($cost);
        $field('Quantity in stock')->fill($stock);
        $browser->getByRole('button', ['name' => 'Save material'])->first()->click();
        $page->assertSee($sku);
    };

    GoldenPathStep::run('materials: add Denim 12oz Indigo', function () use ($page, $addMaterial) {
        $addMaterial('E2E-DEN', 'Denim 12oz Indigo', 'Fabric', 'Metre (m)', '8.500', '250');
        $page->assertSee('250 m')->assertSee(goldenTnd('8.500').' / m');
    }, $page);

    GoldenPathStep::run('materials: add Metal shank button 17mm', function () use ($page, $addMaterial) {
        $addMaterial('E2E-BTN', 'Metal shank button 17mm', 'Fastener', 'Piece (pc)', '0.350', '900');
        $page->assertSee('900 pc')->assertSee(goldenTnd('0.350').' / pc');
    }, $page);

    GoldenPathStep::run('product: add Denim Jacket', function () use ($page, $browser, $field, $choose, $open) {
        $open('products.create');
        $field('SKU')->fill('E2E-JKT');
        $field('Name')->fill('Denim Jacket');
        $choose('Category', 'Apparel');
        $choose('Status', 'Active');
        $field('Labor cost')->fill('12.000');
        $field('Overhead cost (optional)')->fill('3.500');
        $field('Selling price (optional)')->fill('89.000');

        foreach ([['Denim 12oz Indigo', '1.8', '10'], ['Metal shank button 17mm', '8', '0']] as [$material, $quantity, $waste]) {
            $browser->getByRole('button', ['name' => 'Add material', 'exact' => true])->click();
            $browser->getByRole('option', ['name' => $material])->click();
            $field("Quantity per unit for {$material}")->fill($quantity);
            $field("Waste percent for {$material}")->fill($waste);
        }

        $browser->getByRole('button', ['name' => 'Save product'])->first()->click();
        $page->assertScript('/^\/products\/\d+$/.test(location.pathname)');
    }, $page);

    // The product page fills in after its frame: every figure is waited for by its exact value.
    GoldenPathStep::run('product: unit cost', function () use ($page) {
        goldenShows($page, 'section[aria-label="Unit cost"]', 'Unit cost '.goldenTnd('35.130'));
        goldenNoPlaceholders($page);
    }, $page);

    GoldenPathStep::run('product: can produce', function () use ($page) {
        goldenShows($page, 'section[aria-label="Production capacity"]', 'Can produce now 112 units');
    }, $page);

    GoldenPathStep::run('product: bottleneck', function () use ($page) {
        goldenShows($page, '[role="note"]', 'Bottleneck: Metal shank button 17mm.');
    }, $page);

    GoldenPathStep::run('product: margin', function () use ($page) {
        goldenShows($page, 'section[aria-labelledby="cost-breakdown-title"] dl > div', 'Margin '.goldenTnd('53.870').' · 60.53%');
    }, $page);

    GoldenPathStep::run('sale: record 3 at the pre-filled price', function () use ($page, $browser) {
        $browser->getByRole('link', ['name' => 'Record sale', 'exact' => true])->click();
        $page->assertSee('Record a sale');
        // The sales page has its own "Product" filter: stay inside the form's panel.
        $panel = $browser->getByRole('complementary', ['name' => 'Record a sale']);
        expect($panel->getByLabel('Product', true)->textContent())->toContain('Denim Jacket');
        // The selling price (89.000) arrives as the server's string and is left untouched.
        expect($panel->getByLabel('Unit price', true)->inputValue())->toBe('89.0000');
        $panel->getByLabel('Quantity', true)->fill('3');
        // The server's today (UTC), not the browser's default.
        $panel->getByLabel('Date', true)->fill(today()->toDateString());
        $panel->getByRole('button', ['name' => 'Save sale'])->click();
        $page->assertDontSee('Record a sale')->assertSee(goldenTnd('267.000'));
    }, $page);

    GoldenPathStep::run('dashboard: Total sales', function () use ($page, $browser) {
        $browser->getByRole('link', ['name' => 'Dashboard', 'exact' => true])->first()->click();
        goldenShows($page, 'section[aria-label="Key figures"] > *', 'Total sales '.goldenTnd('267.000').' 1 sale · last 30 days');
        goldenNoPlaceholders($page);
        $page->assertNoJavaScriptErrors();
    }, $page);

    GoldenPathStep::run('dashboard: Units sold', function () use ($page) {
        goldenShows($page, 'section[aria-label="Key figures"] > *', 'Units sold 3');
    }, $page);

    GoldenPathStep::run('dashboard: Inventory value', function () use ($page) {
        goldenShows($page, 'section[aria-label="Key figures"] > *', 'Inventory value '.goldenTnd('2,440.000'));
    }, $page);

    GoldenPathStep::run("dashboard: Can't produce", function () use ($page) {
        goldenShows($page, 'section[aria-label="Key figures"] > *', "Can't produce 0 of 1 product");
    }, $page);

    GoldenPathStep::run('dashboard: Top selling products', function () use ($page) {
        goldenShows($page, 'ol[aria-labelledby] > li:first-child', '1 Denim Jacket E2E-JKT 3 units');
    }, $page);

    GoldenPathStep::run('product: still 112 units', function () use ($page, $browser) {
        $browser->locator('ol[aria-labelledby] > li:first-child')->getByRole('link', ['name' => 'Denim Jacket'])->click();
        goldenShows($page, 'section[aria-label="Production capacity"]', 'Can produce now 112 units');
        // S-73: the 30-day sales figure sits under "Recent sales" (3 units at 89.000 = the sale's total).
        goldenShows($page, 'section[aria-labelledby="recent-sales-title"] p', '3 units sold in the last 30 days · '.goldenTnd('267.000'));
        goldenNoPlaceholders($page);
    }, $page);

    // S-56: the jacket starts with nothing on the shelf; "Update stock" sets a count per size.
    GoldenPathStep::run('product: update stock', function () use ($page, $browser) {
        // The figure reads label, button, total: "Update stock" sits on the label's row.
        goldenShows($page, 'section[aria-label="Finished stock"]', 'In stock Update stock 0 units');
        // At the top right of the card, on the same row as "In stock".
        $page->assertScript(<<<'JS'
        (() => {
            const card = document.querySelector('section[aria-label="Finished stock"]');
            const button = Array.from(card.querySelectorAll('button')).find((el) => el.textContent.trim() === 'Update stock');
            const label = Array.from(card.querySelectorAll('span')).find((el) => el.textContent.trim() === 'In stock');
            const c = card.getBoundingClientRect();
            const b = button.getBoundingClientRect();
            const l = label.getBoundingClientRect();
            const sameRow = b.top < l.bottom && b.bottom > l.top;
            const atRight = c.right - b.right <= 32;
            return sameRow && atRight ? 'top right' : JSON.stringify({ sameRow, gapToRightEdge: c.right - b.right });
        })()
        JS, 'top right');
        // Saving reloads only `finished_stock`: the page must never fall back to its placeholders.
        $page->assertScript(<<<'JS'
        (() => {
            window.__placeholderSeen = false;
            new MutationObserver(() => {
                const seen = Array.from(document.querySelectorAll('[role="status"]'))
                    .some((el) => /^(Loading|Checking) /.test(el.textContent.trim()));
                window.__placeholderSeen = window.__placeholderSeen || seen;
            }).observe(document.body, { childList: true, subtree: true });
            return 'watching';
        })()
        JS, 'watching');

        $browser->getByRole('button', ['name' => 'Update stock', 'exact' => true])->click();
        $window = $browser->getByRole('dialog', ['name' => 'Update stock · Denim Jacket']);

        foreach (['S' => '3', 'M' => '4', 'L' => '5', 'XL' => '2'] as $size => $units) {
            $window->getByLabel($size, true)->fill($units);
        }

        $window->getByRole('button', ['name' => 'Save', 'exact' => true])->click();
        $page->assertSee('Stock updated.');
        goldenShows($page, 'section[aria-label="Finished stock"]', 'In stock Update stock 14 units');
        // Screen readers hear the sizes as one line (the chips are hidden from them).
        $page->assertScript(<<<'JS'
        (() => {
            const text = (document.querySelector('section[aria-label="Finished stock"]')?.innerText ?? '').replace(/\s+/g, ' ');
            return text.includes('In stock per size: S 3, M 4, L 5, XL 2') ? 'spoken' : text;
        })()
        JS, 'spoken');
        $page->assertDontSee('Update stock · Denim Jacket');
        $page->assertScript('window.__placeholderSeen', false);
        // Nothing else moved: capacity is still 112.
        goldenShows($page, 'section[aria-label="Production capacity"]', 'Can produce now 112 units');
    }, $page);

    // S-73-b: the three figures are one row of equal cards at the size board's size, and stack on a phone.
    GoldenPathStep::run('product: three figures in one row at 1280 px', function () use ($page) {
        $page->resize(1280, 900);
        $page->assertScript(goldenHeroCards('wide'), 'ok');
        // The currency part of the unit cost is drawn at the size of "units"; the text is still the money text.
        $page->assertScript(<<<'JS'
        (() => {
            const figure = document.querySelector('section[aria-label="Unit cost"]').children[1];
            const code = figure.firstElementChild;
            return code && getComputedStyle(code).fontSize === '18px' && /^TND\s35\.130$/.test(figure.textContent) ? 'ok' : figure.textContent + ' / ' + code?.textContent;
        })()
        JS, 'ok');
    }, $page);

    GoldenPathStep::run('product: the figures stack in order at 375 px', function () use ($page) {
        $page->resize(375, 812);
        $page->assertScript(goldenHeroCards('narrow'), 'ok');
        // Back to the size the other steps use.
        $page->resize(1280, 720);
    }, $page);
});
