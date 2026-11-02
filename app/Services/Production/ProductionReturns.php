<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\ReturnPlan;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use LogicException;

/**
 * What Undo batch gives back (INV-5-e, ADR-0023 decision 2): the batch's units out of its production cut, with ADR-0014's formula
 * `held(r) − held(r − k)` on the amounts the cut remembered (never the current composition), so the batches of one start, undone one
 * by one, give back exactly what it took. Nothing comes back for a material deleted since, or whose unit changed since.
 */
final readonly class ProductionReturns
{
    public function __construct(private CuttingCalculator $calculator) {}

    /**
     * With `$lock` the cut is read `FOR UPDATE`, then its materials in ascending id: only Undo batch asks for that, inside its
     * transaction. Without, the sheet's preview reads the stock saved now.
     *
     * @return array{plan: ReturnPlan, materials: array<int, Material>, cut: ProductionCut}
     */
    public function plan(ProductionBatch $batch, bool $lock = false): array
    {
        $cut = ProductionCut::query()->when($lock, static fn ($query) => $query->lockForUpdate())->findOrFail($batch->production_cut_id);
        $remembered = ProductionCutMaterial::query()->where('production_cut_id', $cut->id)->orderBy('id')->get();
        $ids = $remembered->pluck('material_id')->all();

        $materials = $ids === [] ? collect() : Material::query()
            ->withTrashed()->without('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        $held = new CutBatch($cut->id, $cut->units, $cut->units_returned, array_values($remembered->map(
            static function (ProductionCutMaterial $row) use ($materials): CutBatchMaterial {
                $material = $materials->get($row->material_id) ?? throw new LogicException("Material #{$row->material_id} of a production cut is missing.");

                return new CutBatchMaterial(
                    $material->id,
                    $row->quantity_per_unit,
                    $row->quantity_taken,
                    $material->trashed() ? CutBatchMaterial::DELETED : ($row->unit_id === $material->unit_id ? null : CutBatchMaterial::UNIT_CHANGED),
                );
            },
        )->all()));

        return [
            'plan' => $this->calculator->giveBack([$held], $batch->units, $materials->map(static fn (Material $material): string => $material->quantity_on_hand)->all()),
            'materials' => $materials->all(),
            'cut' => $cut,
        ];
    }
}
