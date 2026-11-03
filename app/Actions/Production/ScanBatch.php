<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\ProductionCounter;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionPath;
use App\Services\Production\ScanMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The scan confirm's button (INV-15-c, AC-4, AC-6, ADR-0024 decision 4): moves the whole batch to its next phase, or puts it in stock
 * at the last one, exactly as Progress does. It first checks, under the production lock and the batch's lock, that nothing changed
 * since the confirm was shown: the batch's phase and units (`moved`) and its next step (`phases`). Then it hands over to
 * {@see MoveBatch} or {@see PutBatchInStock} in the same transaction, marking the move as scanned. Never back, never part of a batch.
 */
final readonly class ScanBatch
{
    public function __construct(private MoveBatch $move, private PutBatchInStock $putInStock) {}

    /**
     * @param  string|int  $from  the step the confirm showed the batch in: 'cutting' or a phase id
     * @param  string|int  $to  the step the confirm offered: 'cutting', a phase id or 'stock'
     * @param  int  $units  the units the confirm showed
     * @return array{message: string} the toast
     *
     * @throws ValidationException on `moved` (the batch changed or its product was deleted) or `phases` (its next step changed)
     */
    public function handle(ProductionBatch $batch, string|int $from, string|int $to, int $units, User $actor): array
    {
        return DB::transaction(function () use ($batch, $from, $to, $units, $actor): array {
            ProductionCounter::lock();
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $path = ProductionPath::current();

            if ($locked->status === ProductionBatchStatus::InStock) {
                throw ValidationException::withMessages(['moved' => ScanMessages::alreadyInStock($locked->label, $locked->finished_at ?? now())]);
            }

            if ($locked->status === ProductionBatchStatus::Undone) {
                throw ValidationException::withMessages(['moved' => ScanMessages::undone($locked->label, $locked->undone_at ?? now())]);
            }

            $now = ProductionPath::refOf($locked->phase_id);

            if ($now !== $from || $locked->units !== $units) {
                throw ValidationException::withMessages(['moved' => ScanMessages::movedSince($locked->label, (string) $path->name($now))]);
            }

            $next = $path->next($from);
            $expected = $next['ref'] ?? 'stock';

            if ($expected !== $to) {
                throw ValidationException::withMessages(['phases' => ScanMessages::phasesChanged($locked->label, $next['name'] ?? ProductionMessages::PUT_IN_STOCK)]);
            }

            if ($expected === 'stock') {
                if ($locked->product->trashed()) {
                    throw ValidationException::withMessages(['moved' => ScanMessages::productDeleted($locked->product->name, $locked->label)]);
                }

                return ['message' => $this->putInStock->handle($locked, $from, $locked->units, $actor, scanned: true)['message']];
            }

            return ['message' => $this->move->handle($locked, $from, $expected, $locked->units, $actor, scanned: true)['message']];
        });
    }
}
