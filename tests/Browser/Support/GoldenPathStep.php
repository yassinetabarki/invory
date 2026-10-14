<?php

declare(strict_types=1);

namespace Tests\Browser\Support;

use Closure;
use Illuminate\Support\Str;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

/**
 * Names the step of the golden path that broke (AC-3 of S-08). Any failure is rethrown with the step in its message
 * and the original kept as `previous`. A plugin assertion already carries its screenshot sentence; any other failure
 * (a Playwright timeout, a direct `expect()`) gets a screenshot taken here when the page is given.
 */
final class GoldenPathStep
{
    /**
     * Runs the body of one step and returns its result. `$page` is the page returned by `visit()`.
     *
     * @template T
     *
     * @param  Closure(): T  $body
     * @return T
     */
    public static function run(string $step, Closure $body, ?object $page = null): mixed
    {
        try {
            return $body();
        } catch (Throwable $original) {
            $message = 'Golden path failed at step "'.$step.'": '.$original->getMessage();

            if ($page !== null && ! str_contains($message, 'A screenshot of the page has been saved')) {
                try {
                    $file = 'golden-path-'.Str::slug($step);
                    $page->screenshot(true, $file);
                    $message .= ' A screenshot of the page has been saved to [tests/Browser/Screenshots/'.$file.'.png].';
                } catch (Throwable) {
                    // The browser may be gone: keep the original error as it is.
                }
            }

            throw new AssertionFailedError($message, 0, $original);
        }
    }
}
