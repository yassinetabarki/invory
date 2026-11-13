<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CutGroup;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\ReturnPlan;
use App\Enums\MaterialStockMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
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
    /** `not_returned` of a take whose colour the material no longer has (INV-21): "Light wash not on this material, not returned". */
    public const COLOUR_MISSING = 'colour_missing';

    public function __construct(private CuttingCalculator $calculator) {}

    /**
     * With `$lock` the cut is read `FOR UPDATE`, then its colours and sizes, then its materials in ascending id: only Undo batch asks
     * for that, inside its transaction. Without, the sheet's preview reads the stock saved now.
     *
     * A batch that holds more units than its cut still has outstanding (it was undone already) gives back nothing.
     *
     * @return array{plan: ReturnPlan, materials: array<int, Material>, colours: array<int, Colour>, cut: ProductionCut, variant: ProductionCutVariant|null} the colours the plan's rows name, keyed by id (one query; the sheet's rows only serialize), and the cut's row of the batch's colour and size, null for a cut made before INV-16
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

        // The colour rows are read after the materials are locked: that lock guards them (ADR-0029 section 3).
        $colourStock = $ids === [] ? [] : MaterialColour::query()->whereIn('material_id', $ids)->get()
            ->mapWithKeys(static fn (MaterialColour $row): array => [$row->material_id.':'.$row->colour_id => $row->quantity_on_hand])->all();

        $perUnit = [];

        if ($variantRows->isNotEmpty()) {
            foreach (ProductionCutVariantMaterial::query()->whereIn('production_cut_variant_id', $variantRows->modelKeys())->orderBy('id')->get() as $row) {
                $perUnit[$row->production_cut_variant_id][$row->material_id] = $row->quantity_per_unit;
            }
        }

        $groups = array_values($variantRows->map(
            static fn (ProductionCutVariant $row): CutGroup => new CutGroup($row->colour_id, $row->size_id, $row->units, $row->units_returned, $perUnit[$row->id] ?? []),
        )->all());

        // Where each take goes, by its stock key (ADR-0029 section 6): the one stock when the material is one stock now, whatever the take
        // came from; else the take's colour (a colourless take: the batch's colour) when the material has it; else nowhere.
        /** @var array<string, string> $stockOnHand the stock each take goes back into, by the take's stock key */
        $stockOnHand = [];
        /** @var array<string, int|null> $goesTo the colour each returnable take goes back into (null: the one stock) */
        $goesTo = [];
        /** @var array<string, true> $colourMissing the takes whose colour the material no longer has */
        $colourMissing = [];

        $held = new CutBatch($cut->id, $cut->units, $cut->units_returned, array_values($remembered->map(
            static function (ProductionCutMaterial $row) use ($materials, $groups, $batch, $colourStock, &$stockOnHand, &$goesTo, &$colourMissing): CutBatchMaterial {
                $material = $materials->get($row->material_id) ?? throw new LogicException("Material #{$row->material_id} of a production cut is missing.");
                $key = CuttingCalculator::stockKey($material->id, $row->colour_id);
                $notReturned = $material->trashed() ? CutBatchMaterial::DELETED : ($row->unit_id === $material->unit_id ? null : CutBatchMaterial::UNIT_CHANGED);

                if ($notReturned === null && $material->stock_mode === MaterialStockMode::PerColour) {
                    $colourId = $row->colour_id ?? $batch->colour_id;
                    $stock = $colourStock[$material->id.':'.$colourId] ?? null;

                    if ($stock === null) {
                        $colourMissing[$key] = true;
                        $notReturned = CutBatchMaterial::UNIT_CHANGED; // not returnable; the plan below says why
                    } else {
                        $stockOnHand[$key] = $stock;
                        $goesTo[$key] = $colourId;
                    }
                } elseif ($notReturned === null) {
                    $stockOnHand[$key] = $material->quantity_on_hand;
                    $goesTo[$key] = null;
                }

                return new CutBatchMaterial(
                    $material->id,
                    $groups === [] ? $row->quantity_per_unit : null,
                    $row->quantity_taken,
                    $notReturned,
                    $row->colour_id,
                );
            },
        )->all()), $groups);

        $variant = $variantRows->first(static fn (ProductionCutVariant $row): bool => $row->colour_id === $batch->colour_id && $row->size_id === $batch->size_id);
        $outstanding = $groups === [] ? $cut->units - $cut->units_returned : ($variant === null ? 0 : $variant->units - $variant->units_returned);

        $plan = $batch->units > $outstanding
            ? new ReturnPlan($batch->units, 0, [], [], true)
            : $this->placed($this->calculator->giveBackFromGroup($held, $batch->colour_id, $batch->size_id, $batch->units, $stockOnHand), $goesTo, $colourMissing);
        $colourIds = array_values(array_unique(array_filter(array_map(static fn (MaterialReturn $return): ?int => $return->colourId, $plan->returns))));

        return [
            'plan' => $plan,
            'materials' => $materials->all(),
            'colours' => $colourIds === [] ? [] : Colour::query()->whereIn('id', $colourIds)->get()->keyBy('id')->all(),
            'cut' => $cut,
            'variant' => $variant,
        ];
    }

    /**
     * The domain's plan with each row placed: a returned row names the colour it goes back into (null: the one stock), a take whose colour
     * the material lost reads {@see self::COLOUR_MISSING} (the domain only knows deleted and unit changed).
     *
     * @param  array<string, int|null>  $goesTo
     * @param  array<string, true>  $colourMissing
     */
    private function placed(ReturnPlan $plan, array $goesTo, array $colourMissing): ReturnPlan
    {
        $returns = array_map(static function (MaterialReturn $return) use ($goesTo, $colourMissing): MaterialReturn {
            $key = CuttingCalculator::stockKey($return->materialId, $return->colourId);

            if (isset($colourMissing[$key]) && $return->notReturned !== CutBatchMaterial::DELETED) {
                return new MaterialReturn($return->materialId, $return->taken, $return->returned, self::COLOUR_MISSING, null, null, $return->colourId);
            }

            return $return->notReturned === null
                ? new MaterialReturn($return->materialId, $return->taken, $return->returned, null, $return->inStock, $return->after, $goesTo[$key] ?? null)
                : $return;
        }, $plan->returns);

        return new ReturnPlan($plan->units, $plan->unitsCovered, $plan->batches, $returns, $plan->nothingTaken);
    }
}
