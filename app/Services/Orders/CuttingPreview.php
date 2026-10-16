<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CutPlan;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\ReturnPlan;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\Product;
use App\Services\Costing\ProductCostingService;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * The materials table of the move window (S-53, ADR-0014 section 8): what "Start cutting N units" would take and what "Move back to
 * To start" would return, worked out by the server so the screen never computes. It reads without locks; the move itself redoes the same maths under the row locks, through
 * {@see self::plan()}, so the window and the refusal can't disagree.
 */
final readonly class CuttingPreview
{
    public function __construct(
        private ProductCostingService $costing,
        private CuttingCalculator $calculator,
    ) {}

    /**
     * The window's data for Start cutting `$units` units of the line, on the stock saved now.
     *
     * @return array{move: OrderLineMove, units: int, plan: CutPlan, materials: array<int, Material>, short_message: string|null}
     *
     * @throws ValidationException under `units` when the product was deleted (a deleted product can't start cutting)
     */
    public function forStartCutting(OrderLine $line, int $units): array
    {
        $product = $line->product()->firstOrFail();

        if ($product->trashed()) {
            throw ValidationException::withMessages(['units' => OrderMessages::productDeleted($product->name)]);
        }

        ['plan' => $plan, 'materials' => $materials] = $this->plan($product, $units);

        return [
            'move' => OrderLineMove::StartCutting,
            'units' => $units,
            'plan' => $plan,
            'materials' => $materials,
            'short_message' => $plan->allowed ? null : OrderMessages::notEnoughStock($plan),
        ];
    }

    /**
     * The window's data for Move back to To start of `$units` units of the line: what the line's remembered cuts give back, latest cut
     * first, on the stock saved now. A deleted product's line previews like any other.
     *
     * @return array{move: OrderLineMove, units: int, plan: ReturnPlan, materials: array<int, Material>, short_message: null}
     */
    public function forBackToStart(OrderLine $line, int $units): array
    {
        ['plan' => $plan, 'materials' => $materials] = $this->returnPlan($line, $units);

        return ['move' => OrderLineMove::BackToStart, 'units' => $units, 'plan' => $plan, 'materials' => $materials, 'short_message' => null];
    }

    /**
     * What moving `$units` units of the line back to To start returns, from the line's cuts that still hold units, newest first, and
     * the stock of their materials as it is read now. The current composition is never read. With `$lock` the cuts are read with
     * `FOR UPDATE` and then their materials, in ascending id (ADR-0014 section 7): only the move itself asks for that, inside its
     * transaction, so the plan is the truth until it ends.
     *
     * A material is not returnable when it is soft-deleted now, or when its unit differs from the cut's (also when the cut kept none).
     *
     * @return array{plan: ReturnPlan, materials: array<int, Material>, cuts: array<int, OrderLineCut>} the materials and cuts keyed by id
     */
    public function returnPlan(OrderLine $line, int $units, bool $lock = false): array
    {
        $cuts = $line->cuts()
            ->whereColumn('units_returned', '<', 'units')
            ->orderByDesc('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        $remembered = $cuts->isEmpty() ? collect() : OrderLineCutMaterial::query()
            ->whereIn('order_line_cut_id', $cuts->keys()->all())
            ->orderBy('id')
            ->get()
            ->groupBy('order_line_cut_id');

        $ids = $remembered->flatten(1)->pluck('material_id')->unique()->all();

        $materials = $ids === [] ? collect() : Material::query()
            ->withTrashed()->without('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        $batches = [];

        foreach ($cuts as $cut) {
            $batches[] = new CutBatch($cut->id, $cut->units, $cut->units_returned, $remembered->get($cut->id, collect())->map(
                static function (OrderLineCutMaterial $row) use ($materials): CutBatchMaterial {
                    $material = $materials->get($row->material_id) ?? throw new LogicException("Material #{$row->material_id} of a cut is missing.");

                    return new CutBatchMaterial(
                        $material->id,
                        $row->quantity_per_unit,
                        $row->quantity_taken,
                        $material->trashed() ? CutBatchMaterial::DELETED : ($row->unit_id === $material->unit_id ? null : CutBatchMaterial::UNIT_CHANGED),
                    );
                },
            )->values()->all());
        }

        return [
            'plan' => $this->calculator->giveBack($batches, $units, $materials->map(static fn (Material $material): string => $material->quantity_on_hand)->all()),
            'materials' => $materials->all(),
            'cuts' => $cuts->all(),
        ];
    }

    /**
     * What cutting `$units` units of the product takes from its composition's materials, on their stock as it is read now. With
     * `$lock` the materials are read with `FOR UPDATE`, in ascending id (ADR-0014 section 7), so the plan is the truth until the
     * transaction ends: only the move itself asks for that, inside its transaction.
     *
     * @return array{plan: CutPlan, materials: array<int, Material>} the materials keyed by id, as the plan was made from them
     */
    public function plan(Product $product, int $units, bool $lock = false): array
    {
        $components = $product->components()->get();
        $ids = $components->pluck('material_id')->all();

        // A soft-deleted material stays readable (a deleted product's composition may still point at it), like in costing.
        $materials = $ids === [] ? collect() : Material::query()
            ->withTrashed()->without('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        foreach ($components as $component) {
            $component->setRelation('material', $materials->get($component->material_id));
        }

        $product->setRelation('components', $components);

        return [
            'plan' => $this->calculator->take($this->costing->bomLines($product), $units),
            'materials' => $materials->all(),
        ];
    }
}
