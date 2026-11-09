<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CutGroup;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\ReturnPlan;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use App\Models\ProductionCutVariant;
use App\Models\ProductionCutVariantMaterial;
use LogicException;

/**
 * What Undo batch gives back (INV-5-e, ADR-0023 decision 2; per colour and size since INV-16, ADR-0027 decision 7): the batch's units out
 * of its production cut, with `held(r) − held(r − k)` on the amounts the cut remembered (never the current composition), so the batches
 * of one start, undone one by one, give back exactly what it took. A cut remembers its amounts per colour and size; one made before INV-16
 * has no such rows and is read as one group with the amount per unit it stored. Nothing comes back for a material deleted since, or whose
 * unit changed since.
 */
final readonly class ProductionReturns
{
    public function __construct(private CuttingCalculator $calculator) {}

    /**
     * With `$lock` the cut is read `FOR UPDATE`, then its colours and sizes, then its materials in ascending id: only Undo batch asks
     * for that, inside its transaction. Without, the sheet's preview reads the stock saved now.
     *
     * A batch that holds more units than its cut still has outstanding (it was undone already) gives back nothing.
     *
     * @return array{plan: ReturnPlan, materials: array<int, Material>, cut: ProductionCut, variant: ProductionCutVariant|null} the cut's row of the batch's colour and size, null for a cut made before INV-16
     */
    public function plan(ProductionBatch $batch, bool $lock = false): array
    {
        $cut = ProductionCut::query()->when($lock, static fn ($query) => $query->lockForUpdate())->findOrFail($batch->production_cut_id);
        $variantRows = ProductionCutVariant::query()->where('production_cut_id', $cut->id)->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())->get();
        $remembered = ProductionCutMaterial::query()->where('production_cut_id', $cut->id)->orderBy('id')->get();
        $ids = $remembered->pluck('material_id')->all();

        $materials = $ids === [] ? collect() : Material::query()
            ->withTrashed()->without('category')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');

        $perUnit = [];

        if ($variantRows->isNotEmpty()) {
            foreach (ProductionCutVariantMaterial::query()->whereIn('production_cut_variant_id', $variantRows->modelKeys())->orderBy('id')->get() as $row) {
                $perUnit[$row->production_cut_variant_id][$row->material_id] = $row->quantity_per_unit;
            }
        }

        $groups = array_values($variantRows->map(
            static fn (ProductionCutVariant $row): CutGroup => new CutGroup($row->colour_id, $row->size_id, $row->units, $row->units_returned, $perUnit[$row->id] ?? []),
        )->all());

        $held = new CutBatch($cut->id, $cut->units, $cut->units_returned, array_values($remembered->map(
            static function (ProductionCutMaterial $row) use ($materials, $groups): CutBatchMaterial {
                $material = $materials->get($row->material_id) ?? throw new LogicException("Material #{$row->material_id} of a production cut is missing.");

                return new CutBatchMaterial(
                    $material->id,
                    $groups === [] ? $row->quantity_per_unit : null,
                    $row->quantity_taken,
                    $material->trashed() ? CutBatchMaterial::DELETED : ($row->unit_id === $material->unit_id ? null : CutBatchMaterial::UNIT_CHANGED),
                );
            },
        )->all()), $groups);

        $variant = $variantRows->first(static fn (ProductionCutVariant $row): bool => $row->colour_id === $batch->colour_id && $row->size_id === $batch->size_id);
        $outstanding = $groups === [] ? $cut->units - $cut->units_returned : ($variant === null ? 0 : $variant->units - $variant->units_returned);

        return [
            'plan' => $batch->units > $outstanding
                ? new ReturnPlan($batch->units, 0, [], [], true)
                : $this->calculator->giveBackFromGroup($held, $batch->colour_id, $batch->size_id, $batch->units, $materials->map(static fn (Material $material): string => $material->quantity_on_hand)->all()),
            'materials' => $materials->all(),
            'cut' => $cut,
            'variant' => $variant,
        ];
    }
}
