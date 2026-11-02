<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Actions\Materials\AppendStockMovement;
use App\Actions\Products\UpdateFinishedStock;
use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductionCounter;
use App\Models\ProductionCut;
use App\Models\SizeList;
use App\Models\User;
use App\Services\Orders\CuttingPreview;
use App\Services\Orders\OrderMessages;
use App\Services\Production\ProductionMessages;
use App\Services\Products\SizeLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Start batch (INV-5-c, ADR-0023): takes the product's materials out of stock for the whole total, as one production cut, and writes
 * one batch per colour and size with units, In cutting since today. One transaction, all or nothing.
 *
 * Lock order (ADR-0023 decision 6): the workshop's production counter, the product (the lock Update product, Update stock and Delete
 * product take), then its materials in ascending id (inside {@see CuttingPreview::plan()}). The plan is made on the stock read under
 * those locks, with ADR-0014's maths, so the window and the refusal can't disagree; every figure written is the plan's.
 */
final readonly class StartBatch
{
    public function __construct(
        private CuttingPreview $preview,
        private AppendStockMovement $appendMovement,
        private SizeLists $sizeLists,
    ) {}

    /**
     * @param  array<int, array<int, int>>  $grid  units by colour id then size id, 0 for an empty field
     * @return array{product: string, units: int, batches: list<array{number: string, colour: string, size: string|null, units: int}>}
     *
     * @throws ValidationException on `product_id` (deleted) or `units` (changed colours or sizes, nothing typed, short stock)
     */
    public function handle(int $productId, array $grid, User $actor): array
    {
        return DB::transaction(function () use ($productId, $grid, $actor): array {
            $counter = ProductionCounter::lock();
            $product = Product::withTrashed()->lockForUpdate()->findOrFail($productId);

            if ($product->trashed()) {
                throw ValidationException::withMessages(['product_id' => ProductionMessages::startDeleted($product->name)]);
            }

            $colours = $product->colours()->get();
            $sizeList = $this->sizeLists->category($product->category_id)->sizeList;
            $sizes = $sizeList->sizes;

            if (! UpdateFinishedStock::matches($grid, $colours->modelKeys(), $sizes->modelKeys())) {
                throw ValidationException::withMessages(['units' => ProductionMessages::startChanged($product->name)]);
            }

            // Colours in list order, sizes in size order: the order the batches are numbered in.
            $variants = [];

            foreach ($colours as $colour) {
                foreach ($sizes as $size) {
                    $units = $grid[$colour->id][$size->id];

                    if ($units > 0) {
                        $variants[] = ['colour' => $colour, 'size' => $size, 'units' => $units];
                    }
                }
            }

            $total = array_sum(array_column($variants, 'units'));

            if ($total < 1) {
                throw ValidationException::withMessages(['units' => ProductionMessages::nothingToStart()]);
            }

            ['plan' => $plan, 'materials' => $materials] = $this->preview->plan($product, $total, lock: true);

            if (! $plan->allowed) {
                throw ValidationException::withMessages(['units' => OrderMessages::notEnoughStock($plan)]);
            }

            $cut = new ProductionCut(['units' => $total, 'units_returned' => 0]);
            $cut->forceFill(['workshop_id' => $product->workshop_id, 'product_id' => $product->id, 'user_id' => $actor->id])->save();
            $today = today()->toDateString();

            foreach ($plan->takes as $take) {
                $material = $materials[$take->materialId];
                $after = $take->after ?? throw new LogicException("Material #{$take->materialId} is short in an allowed start.");

                $material->quantity_on_hand = $after;
                $material->updated_by = $actor->id;
                $material->save();

                $this->appendMovement->handle($material, StockMovementType::Cutting, $today, $actor, [
                    'quantity' => $take->needed,
                    'quantity_before' => $take->inStock,
                    'quantity_after' => $after,
                    'product_id' => $product->id,
                    'units' => $total,
                ]);

                $remembered = $cut->materials()->make([
                    'material_id' => $material->id,
                    'unit_id' => $material->unit_id,
                    'quantity_per_unit' => $take->perUnitExact,
                    'quantity_taken' => $take->needed,
                ]);
                $remembered->workshop_id = $cut->workshop_id;
                $remembered->save();
            }

            $oneSize = $sizeList->code === SizeList::ONE_SIZE;
            $started = [];

            foreach ($variants as $variant) {
                $batch = new ProductionBatch([
                    'number' => $counter->next(),
                    'product_id' => $product->id,
                    'colour_id' => $variant['colour']->id,
                    'size_id' => $variant['size']->id,
                    'production_cut_id' => $cut->id,
                    'units' => $variant['units'],
                    'status' => ProductionBatchStatus::InProduction,
                    'phase_id' => null,
                    'phase_since' => $today,
                    'started_on' => $today,
                ]);
                $batch->forceFill(['workshop_id' => $product->workshop_id, 'user_id' => $actor->id])->save();

                $move = new ProductionBatchMove([
                    'production_batch_id' => $batch->id,
                    'kind' => ProductionBatchMoveKind::Start,
                    'units' => $batch->units,
                    'from_label' => null,
                    'to_label' => ProductionMessages::IN_CUTTING,
                ]);
                $move->forceFill(['workshop_id' => $product->workshop_id, 'user_id' => $actor->id])->save();

                $started[] = [
                    'number' => $batch->label,
                    'colour' => $variant['colour']->name,
                    'size' => $oneSize ? null : $variant['size']->label,
                    'units' => $batch->units,
                ];
            }

            return ['product' => $product->name, 'units' => $total, 'batches' => $started];
        });
    }
}
