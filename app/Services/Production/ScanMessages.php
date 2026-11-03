<?php

declare(strict_types=1);

namespace App\Services\Production;

use Carbon\CarbonInterface;

/**
 * Every sentence of the Scan page (INV-15, AC-3 to AC-6), so the lookup, the Action and their tests say the same words. Days read
 * "28 Oct 2026".
 */
final readonly class ScanMessages
{
    /** "B-0139 was put in stock on 28 Oct 2026. Nothing left to do." */
    public static function alreadyInStock(string $batch, CarbonInterface $on): string
    {
        return __(':batch was put in stock on :day. Nothing left to do.', ['batch' => $batch, 'day' => self::day($on)]);
    }

    /** "B-0150 was undone on 30 Oct 2026: its materials went back to stock. Nothing to move." */
    public static function undone(string $batch, CarbonInterface $on): string
    {
        return __(':batch was undone on :day: its materials went back to stock. Nothing to move.', ['batch' => $batch, 'day' => self::day($on)]);
    }

    /** A code that isn't a ticket, or another workshop's ticket: it never says that another workshop exists. */
    public static function unknownCode(): string
    {
        return __('This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.');
    }

    /** "No batch B-0999 in your workshop. Check the number on the ticket." */
    public static function unknownNumber(string $batch): string
    {
        return __('No batch :batch in your workshop. Check the number on the ticket.', ['batch' => $batch]);
    }

    public static function notANumber(): string
    {
        return __('A batch number looks like B-0142. Check what you typed.');
    }

    /** "B-0147 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." */
    public static function recentMove(string $batch, string $to, int $seconds, string $who, string $next): string
    {
        return __(':batch moved to :to :ago (:who). Scanning again moves it on to :next.', [
            'batch' => $batch, 'to' => $to, 'ago' => self::ago($seconds), 'who' => $who, 'next' => $next,
        ]);
    }

    /** "B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move it on." */
    public static function movedSince(string $batch, string $now): string
    {
        return __(':batch has moved since you scanned it: it is now in :now. Scan it again to move it on.', ['batch' => $batch, 'now' => $now]);
    }

    /** "The phases have changed since you scanned. B-0147 now goes to Quality check." */
    public static function phasesChanged(string $batch, string $next): string
    {
        return __('The phases have changed since you scanned. :batch now goes to :next.', ['batch' => $batch, 'next' => $next]);
    }

    /** "Denim Jacket was deleted, so B-0148 can't be put in stock." */
    public static function productDeleted(string $product, string $batch): string
    {
        return __(':product was deleted, so :batch can\'t be put in stock.', ['product' => $product, 'batch' => $batch]);
    }

    /** Who made a move whose account was deleted since. */
    public static function someone(): string
    {
        return __('someone');
    }

    /** "12 seconds ago", "1 second ago", "1 minute ago". */
    private static function ago(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return $seconds < 60
            ? trans_choice('{0} just now|{1} :count second ago|[2,*] :count seconds ago', $seconds, ['count' => $seconds])
            : trans_choice('{1} :count minute ago|[2,*] :count minutes ago', intdiv($seconds, 60), ['count' => intdiv($seconds, 60)]);
    }

    private static function day(CarbonInterface $on): string
    {
        return $on->format('j M Y');
    }
}
