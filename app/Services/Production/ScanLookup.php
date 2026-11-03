<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Actions\Production\ScanBatch;
use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductSizeStock;

/**
 * What a scan or a typed entry stands for (INV-15-c, AC-3 to AC-6, ADR-0024): a ticket link (".../scan/t/<code>", from any host), or a
 * batch number typed as "B-0147", "b-0147", "B147", "0147" or "147". The batch is looked up in the current workshop only (the workshop
 * scope), so another workshop's code or number is simply not found. The answer is the confirm of the batch's next step, or the reason
 * nothing can move. Read-only: the move itself is {@see ScanBatch}.
 */
final readonly class ScanLookup
{
    /** A move less than this many seconds old shows the "scanned twice quickly" warning. */
    public const RECENT_SECONDS = 120;

    public function __construct(private BatchNames $names) {}

    /** @return array{confirm: array<string, mixed>|null, refusal: string|null} */
    public function find(string $entry): array
    {
        $entry = trim($entry);

        if (preg_match('~/scan/t/([A-Za-z0-9]{'.ProductionBatch::TICKET_CODE_LENGTH.'})/?\z~', $entry, $link) === 1) {
            return $this->byCode($link[1]);
        }

        // Any other link (a QR code that isn't one of our tickets, or a ticket link cut short) is a code, not a typed number.
        if (str_contains($entry, '://') || str_contains($entry, '/scan/t/')) {
            return self::refusal(ScanMessages::unknownCode());
        }

        if (preg_match('/\A[Bb]?-?0*([0-9]{1,9})\z/', $entry, $typed) === 1) {
            $number = (int) $typed[1];
            $batch = ProductionBatch::query()->where('number', $number)->first();

            return $batch instanceof ProductionBatch
                ? $this->answer($batch)
                : self::refusal(ScanMessages::unknownNumber(ProductionBatch::labelOf($number)));
        }

        return self::refusal(ScanMessages::notANumber());
    }

    /** @return array{confirm: array<string, mixed>|null, refusal: string|null} */
    public function byCode(string $code): array
    {
        $batch = ProductionBatch::query()->where('ticket_code', $code)->first();

        return $batch instanceof ProductionBatch ? $this->answer($batch) : self::refusal(ScanMessages::unknownCode());
    }

    /** @return array{confirm: array<string, mixed>|null, refusal: string|null} */
    public function answer(ProductionBatch $batch): array
    {
        if ($batch->status === ProductionBatchStatus::InStock) {
            return self::refusal(ScanMessages::alreadyInStock($batch->label, $batch->finished_at ?? $batch->updated_at));
        }

        if ($batch->status === ProductionBatchStatus::Undone) {
            return self::refusal(ScanMessages::undone($batch->label, $batch->undone_at ?? $batch->updated_at));
        }

        $batch->loadMissing(['product', 'colour', 'size']);
        $path = ProductionPath::current();
        $from = ProductionPath::refOf($batch->phase_id);
        $next = $path->next($from);

        if ($next === null && $batch->product->trashed()) {
            return self::refusal(ScanMessages::productDeleted($batch->product->name, $batch->label));
        }

        return ['confirm' => $this->confirm($batch, $path, $from, $next), 'refusal' => null];
    }

    /**
     * @param  array{ref: 'cutting'|int, name: string}|null  $next
     * @return array<string, mixed>
     */
    private function confirm(ProductionBatch $batch, ProductionPath $path, string|int $from, ?array $next): array
    {
        $toName = $next['name'] ?? ProductionMessages::PUT_IN_STOCK;
        $steps = [...$path->steps, ['ref' => 'stock', 'name' => ProductionMessages::PUT_IN_STOCK]];
        $current = (int) array_search($from, array_column($steps, 'ref'), true);
        $shelf = null;

        if ($next === null) {
            $now = (int) ProductSizeStock::query()
                ->where(['product_id' => $batch->product_id, 'colour_id' => $batch->colour_id, 'size_id' => $batch->size_id])
                ->value('units');
            $shelf = ['now' => $now, 'after' => $now + $batch->units];
        }

        return [
            'batch_id' => $batch->id,
            'number' => $batch->label,
            'units' => $batch->units,
            'product' => $batch->product->name,
            'variant' => $this->names->dotted($batch),
            'started_on' => $batch->started_on->toDateString(),
            'path' => array_map(static fn (int $i, array $step): array => [
                'name' => $step['name'],
                'state' => match (true) {
                    $i < $current => 'done',
                    $i === $current => 'now',
                    $i === $current + 1 => 'next',
                    default => 'later',
                },
            ], array_keys($steps), $steps),
            'from' => $from,
            'to' => $next['ref'] ?? 'stock',
            'to_name' => $toName,
            'shelf' => $shelf,
            'warning' => $this->warning($batch, $toName),
        ];
    }

    /** "B-0147 moved to Finishing 12 seconds ago (Admin). …" when its last move on or back is less than 2 minutes old. */
    private function warning(ProductionBatch $batch, string $next): ?string
    {
        $last = ProductionBatchMove::query()
            ->where('production_batch_id', $batch->id)
            ->whereIn('kind', [ProductionBatchMoveKind::Forward->value, ProductionBatchMoveKind::Back->value])
            ->latest('id')
            ->first();

        if (! $last instanceof ProductionBatchMove) {
            return null;
        }

        $seconds = (int) $last->created_at->diffInSeconds(now(), true);

        if ($seconds >= self::RECENT_SECONDS) {
            return null;
        }

        $who = $last->user()->value('name');

        return ScanMessages::recentMove($batch->label, (string) $last->to_label, $seconds, is_string($who) ? $who : ScanMessages::someone(), $next);
    }

    /** @return array{confirm: null, refusal: string} */
    private static function refusal(string $message): array
    {
        return ['confirm' => null, 'refusal' => $message];
    }
}
