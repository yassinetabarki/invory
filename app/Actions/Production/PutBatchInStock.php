<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Actions\Restocks\RecordRestock;
use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductionCounter;
use App\Models\ProductRestock;
use App\Models\User;
use App\Services\Production\BatchNames;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionPath;
use App\Services\Products\SizeLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Put in stock (INV-5-e, AC-6, ADR-0023 decision 4): adds units of a batch at the end of its path to the finished stock, through
 * {@see RecordRestock} (today, Reference = the batch number, Note "From production"), so the shelf, Restocks, Undo restock and the
 * 1,000,000 limit have one writer. All its units: the batch leaves Progress. Part of them: it keeps the rest, its number and its
 * phase. Lock order: the production lock, the batch, then the product (inside RecordRestock). Materials never change.
 */
final readonly class PutBatchInStock
{
    public function __construct(
        private RecordRestock $recordRestock,
        private SizeLists $sizeLists,
        private BatchNames $names,
    ) {}

    /**
     * @param  bool  $scanned  made from the Scan page (INV-15): written on the move, for "Your scans today"
     * @return array{message: string, restock: ProductRestock}
     *
     * @throws ValidationException on `moved` (stale page) or `units` (more than the batch holds, a deleted product, over 1,000,000)
     */
    public function handle(ProductionBatch $batch, string|int $from, int $units, User $actor, bool $scanned = false): array
    {
        return DB::transaction(function () use ($batch, $from, $units, $actor, $scanned): array {
            ProductionCounter::lock();
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $path = ProductionPath::current();

            if (! $locked->isInProduction() || ProductionPath::refOf($locked->phase_id) !== $from || ! $path->isLast($from)) {
                throw ValidationException::withMessages(['moved' => ProductionMessages::movedOn()]);
            }

            if ($units > $locked->units) {
                throw ValidationException::withMessages(['units' => ProductionMessages::stockLimit($locked->units)]);
            }

            $product = $locked->product;

            if ($product->trashed()) {
                throw ValidationException::withMessages(['units' => ProductionMessages::stockDeleted($product->name)]);
            }

            // The product's whole grid, 0 everywhere but the batch's colour and size: what RecordRestock expects.
            $grid = [];

            foreach ($product->colours()->pluck('colours.id') as $colourId) {
                foreach ($this->sizeLists->category($product->category_id)->sizeList->sizes as $size) {
                    $grid[$colourId][$size->id] = $colourId === $locked->colour_id && $size->id === $locked->size_id ? $units : 0;
                }
            }

            try {
                $restock = $this->recordRestock->handle([
                    'restocked_on' => today()->toDateString(),
                    'reference' => $locked->label,
                    'note' => ProductionMessages::FROM_PRODUCTION,
                    'products' => [['product_id' => $product->id, 'units' => $grid]],
                ], $actor);
            } catch (ValidationException $refused) {
                $overLimit = collect($refused->errors())->keys()->contains(static fn (string $key): bool => str_starts_with($key, 'products.0.units.'));

                throw ValidationException::withMessages(['units' => $overLimit
                    ? ProductionMessages::shelfLimit($this->names->variant($locked))
                    : ProductionMessages::movedOn()]);
            }

            if ($units === $locked->units) {
                $locked->forceFill([
                    'status' => ProductionBatchStatus::InStock,
                    'phase_id' => null,
                    'finished_at' => now(),
                    'product_restock_id' => $restock->id,
                ])->save();
            } else {
                $locked->forceFill(['units' => $locked->units - $units, 'product_restock_id' => $restock->id])->save();
            }

            $entry = new ProductionBatchMove([
                'production_batch_id' => $locked->id,
                'kind' => ProductionBatchMoveKind::Stock,
                'units' => $units,
                'from_label' => $path->name($from),
                'to_label' => ProductionMessages::PUT_IN_STOCK,
                'scanned' => $scanned,
            ]);
            $entry->forceFill(['workshop_id' => $locked->workshop_id, 'user_id' => $actor->id])->save();

            return ['message' => ProductionMessages::putInStock($units, $this->names->what($locked)), 'restock' => $restock];
        });
    }
}
