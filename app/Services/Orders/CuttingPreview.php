<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\CuttingCalculator;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Costing\ProductCostingService;
use Illuminate\Validation\ValidationException;

/**
 * The materials table of the move window (S-53, ADR-0014 section 8): what "Start cutting N units" would take, worked out by the
 * server so the screen never computes. It reads without locks; the move itself redoes the same maths under the row locks, through
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
