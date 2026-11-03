<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Enums\ProductionBatchMoveKind;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductionCounter;
use App\Models\User;
use App\Services\Production\BatchNames;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves units of a batch one phase on or back (INV-5-d, AC-5, ADR-0023). Under the workshop's production lock, then the batch's row:
 * the batch must still be in production in `$from`, and `$to` must still be the phase right after or before it, else the page was
 * stale ("These units have moved on since you opened this page."). All its units: the batch changes phase, since today. Part of them:
 * the batch keeps the rest, its phase and its day; the units moved become a new batch with the next number. Moves never touch
 * materials or the shelf; forward from the last phase is Put in stock ({@see PutBatchInStock}), not a move.
 */
final readonly class MoveBatch
{
    public function __construct(private BatchNames $names) {}

    /**
     * @param  bool  $scanned  made from the Scan page (INV-15): written on the move, for "Your scans today"
     * @return array{message: string, batch: ProductionBatch} the toast, and the batch that holds the units moved
     *
     * @throws ValidationException on `moved` (stale page) or `units` (more than the batch holds)
     */
    public function handle(ProductionBatch $batch, string|int $from, string|int $to, int $units, User $actor, bool $scanned = false): array
    {
        return DB::transaction(function () use ($batch, $from, $to, $units, $actor, $scanned): array {
            $counter = ProductionCounter::lock();
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $path = ProductionPath::current();

            if (! $locked->isInProduction() || ProductionPath::refOf($locked->phase_id) !== $from) {
                throw ValidationException::withMessages(['moved' => ProductionMessages::movedOn()]);
            }

            $kind = match (true) {
                ($path->next($from)['ref'] ?? null) === $to => ProductionBatchMoveKind::Forward,
                ($path->previous($from)['ref'] ?? null) === $to => ProductionBatchMoveKind::Back,
                default => throw ValidationException::withMessages(['moved' => ProductionMessages::movedOn()]),
            };

            if ($units > $locked->units) {
                throw ValidationException::withMessages(['units' => ProductionMessages::moveLimit($locked->units)]);
            }

            $today = today()->toDateString();
            $phaseId = ProductionPath::phaseIdOf($to);
            $moved = $locked;

            if ($units === $locked->units) {
                $locked->update(['phase_id' => $phaseId, 'phase_since' => $today]);
            } else {
                $locked->update(['units' => $locked->units - $units]);

                $moved = $locked->replicate(['number', 'ticket_code', 'units', 'phase_id', 'phase_since', 'split_from_id', 'created_at', 'updated_at']);
                $moved->fill([
                    'number' => $counter->next(),
                    'units' => $units,
                    'phase_id' => $phaseId,
                    'phase_since' => $today,
                    'split_from_id' => $locked->id,
                ]);
                $moved->save();
            }

            $entry = new ProductionBatchMove([
                'production_batch_id' => $moved->id,
                'kind' => $kind,
                'units' => $units,
                'from_label' => $path->name($from),
                'to_label' => $path->name($to),
                'scanned' => $scanned,
            ]);
            $entry->forceFill(['workshop_id' => $moved->workshop_id, 'user_id' => $actor->id])->save();

            $message = ProductionMessages::moved($units, $this->names->what($locked), (string) $path->name($to));

            if ($moved->isNot($locked)) {
                $message .= ' '.ProductionMessages::split($locked->label, $moved->label);
            }

            return ['message' => $message, 'batch' => $moved];
        });
    }
}
