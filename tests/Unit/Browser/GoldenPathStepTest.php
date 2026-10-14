<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Tests\Browser\Support\GoldenPathStep;

it('rethrows a failing step with the step name, the original message and one screenshot', function () {
    expect(GoldenPathStep::run('sign in', fn () => 'ok'))->toBe('ok');

    try {
        GoldenPathStep::run('dashboard: Total sales', fn () => throw new RuntimeException('expected "2,440.000" but found "0.000"'));
        $this->fail('The step should have thrown.');
    } catch (AssertionFailedError $e) {
        expect($e->getMessage())
            ->toStartWith('Golden path failed at step "dashboard: Total sales": ')
            ->toContain('expected "2,440.000" but found "0.000"')
            ->and($e->getPrevious())->toBeInstanceOf(RuntimeException::class);
    }

    // The stub matches the plugin's `screenshot(bool $fullPage, ?string $filename)`.
    $page = new class
    {
        /** @var list<array{bool, string}> */
        public array $shots = [];

        public function screenshot(bool $fullPage = true, ?string $filename = null): static
        {
            $this->shots[] = [$fullPage, (string) $filename];

            return $this;
        }
    };

    try {
        GoldenPathStep::run("dashboard: Can't produce", fn () => throw new RuntimeException('timeout clicking "Save"'), $page);
        $this->fail('The step should have thrown.');
    } catch (AssertionFailedError $e) {
        expect($page->shots)->toBe([[true, 'golden-path-dashboard-cant-produce']])
            ->and($e->getMessage())
            ->toStartWith('Golden path failed at step "dashboard: Can\'t produce": timeout clicking "Save"')
            ->toEndWith(' A screenshot of the page has been saved to [tests/Browser/Screenshots/golden-path-dashboard-cant-produce.png].');
    }

    // An assertion that already carries the plugin's screenshot sentence is not photographed twice.
    $page->shots = [];
    $plugin = 'x. A screenshot of the page has been saved to [Tests/Browser/Screenshots/a.png].';

    try {
        GoldenPathStep::run('sign in', fn () => throw new RuntimeException($plugin), $page);
        $this->fail('The step should have thrown.');
    } catch (AssertionFailedError $e) {
        expect($page->shots)->toBe([])->and($e->getMessage())->toEndWith($plugin);
    }
});
