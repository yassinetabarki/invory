<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class UpdateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private AppendStockMovement $appendMovement,
        private MaterialStockWriter $stock,
        private SwitchMaterialStock $switch,
        private PhotoStorage $photos,
    ) {}

    /**
     * A changed `quantity_on_hand` is logged as a `correction` (before and after, no reason: the form has none) and a changed
     * `unit_cost` as a `price_change`, both dated today. Equal values are compared as numbers, so "250" over "250.0000" is no change.
     *
     * A material kept per colour (INV-21) is edited within its mode: each colour whose quantity changed gets a `correction` and each
     * colour whose cost changed a `price_change`, a colour added is a new row (a `correction` from 0 when above 0), and a colour left
     * out goes only at 0 and with no entry naming it. The entries of one save share an `entry_group`.
     *
     * A save may also switch the stock mode or the cost mode (INV-21-g, {@see SwitchMaterialStock}): the switch comes first, and what
     * the form holds besides is then written as an edit of the new mode. Back to one stock, `quantity_on_hand` is ignored: the total of
     * the colours is the one stock.
     *
     * A `photo` upload replaces the photo (it wins over `remove_photo`) and `remove_photo` clears it. The new file is stored
     * before the transaction and deleted again if it throws; the old file is deleted after it committed.
     *
     * @param  array<string, mixed>  $data  validated input, with `category` and `unit` as codes (and `photo` / `remove_photo`)
     *
     * @throws ValidationException when a colour that can't go is left out
     */
    public function handle(Material $material, array $data, User $actor): Material
    {
        return $this->photos->persist('materials', $data, $material->photo_path, fn (array $attributes, array $photo): Material => DB::transaction(function () use ($material, $attributes, $photo, $actor): Material {
            // The lock reads the material again (and, per colour, its colour rows) so every "before" below is what is stored.
            $this->stock->lock($material);

            $joined = $material->stock_mode === MaterialStockMode::PerColour && ($attributes['stock_mode'] ?? null) === MaterialStockMode::Single->value;

            $this->switch->handle(
                $material,
                MaterialStockMode::tryFrom((string) ($attributes['stock_mode'] ?? '')) ?? $material->stock_mode,
                MaterialCostMode::tryFrom((string) ($attributes['cost_mode'] ?? '')) ?? $material->cost_mode,
                $attributes,
                $actor,
            );

            if ($material->stock_mode === MaterialStockMode::PerColour) {
                return $this->updateColours($material, $attributes, $photo, $actor);
            }

            $material->fill($this->resolveCodes->handle(Arr::except($attributes, ['stock_mode', 'cost_mode', 'colours', 'quantity_on_hand', 'unit_cost'])))->forceFill($photo);
            $material->updated_by = $actor->id;
            $material->save();

            // Just joined: the colours' total and the kept cost are already the material's, with their entries.
            if (! $joined) {
                $this->updateOneStock($material, $attributes, $actor);
            }

            // The category and unit relations were loaded before the change.
            return $material->load(['category', 'unit']);
        }));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateOneStock(Material $material, array $attributes, User $actor): void
    {
        $stockBefore = BigDecimal::of($material->quantity_on_hand);
        $costBefore = BigDecimal::of($material->unit_cost);

        // Only what the input carries is written (the route always sends both; a direct call may send neither).
        if (isset($attributes['quantity_on_hand'])) {
            $this->stock->setQuantity($material, null, (string) $attributes['quantity_on_hand']);
        }

        if (isset($attributes['unit_cost'])) {
            $this->stock->setCost($material, (string) $attributes['unit_cost']);
        }

        $stockAfter = BigDecimal::of($material->quantity_on_hand);
        $costAfter = BigDecimal::of($material->unit_cost);
        $today = today()->toDateString();

        if (! $stockAfter->isEqualTo($stockBefore)) {
            $this->appendMovement->handle($material, StockMovementType::Correction, $today, $actor, [
                'quantity_before' => (string) $stockBefore,
                'quantity_after' => (string) $stockAfter,
            ]);
        }

        if (! $costAfter->isEqualTo($costBefore)) {
            $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                'unit_cost_before' => (string) $costBefore,
                'unit_cost_after' => (string) $costAfter,
                'quantity_after' => (string) $stockAfter,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array{photo_path?: string|null}  $photo
     */
    private function updateColours(Material $material, array $attributes, array $photo, User $actor): Material
    {
        $material->load('colours.colour');

        $perColourCost = $material->cost_mode === MaterialCostMode::PerColour;
        $submitted = [];

        foreach (is_array($attributes['colours'] ?? null) ? $attributes['colours'] : [] as $row) {
            $row = (array) $row;
            $submitted[(int) Arr::get($row, 'colour_id')] = $row;
        }

        $this->refuseRemovals($material, array_keys($submitted));

        // The free-text `color` is hidden while the stock is per colour: it is left as it is (AC-1). The stock and the cost are the writer's.
        $material->fill($this->resolveCodes->handle(Arr::except($attributes, ['stock_mode', 'cost_mode', 'colours', 'unit_cost', 'color'])))->forceFill($photo);
        $material->updated_by = $actor->id;
        $material->save();

        $today = today()->toDateString();
        $group = (string) Str::uuid();

        if (! $perColourCost && isset($attributes['unit_cost'])) {
            $before = BigDecimal::of($material->unit_cost);
            $after = BigDecimal::of((string) $attributes['unit_cost'])->toScale(4, RoundingMode::HalfUp);

            if (! $after->isEqualTo($before)) {
                $this->stock->setCost($material, $after);
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                    'unit_cost_before' => (string) $before,
                    'unit_cost_after' => (string) $after,
                    'quantity_after' => $material->quantity_on_hand,
                    'entry_group' => $group,
                ]);
            }
        }

        $rows = $material->colours->keyBy('colour_id');

        foreach ($submitted as $colourId => $row) {
            $quantity = BigDecimal::of((string) Arr::get($row, 'quantity_on_hand'))->toScale(4, RoundingMode::HalfUp);
            $cost = $perColourCost ? BigDecimal::of((string) Arr::get($row, 'unit_cost'))->toScale(4, RoundingMode::HalfUp) : null;
            $current = $rows->get($colourId);

            if ($current === null) {
                $this->stock->addColour($material, $colourId, $quantity, $cost);
                $this->logQuantity($material, $colourId, BigDecimal::zero(), $quantity, $group, $actor);

                continue;
            }

            $quantityBefore = BigDecimal::of($current->quantity_on_hand);

            if (! $quantity->isEqualTo($quantityBefore)) {
                $this->stock->setQuantity($material, $colourId, $quantity);
                $this->logQuantity($material, $colourId, $quantityBefore, $quantity, $group, $actor);
            }

            if ($cost !== null && $current->unit_cost !== null && ! $cost->isEqualTo($current->unit_cost)) {
                $this->stock->setColourCost($material, $colourId, $cost);
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                    'unit_cost_before' => $current->unit_cost,
                    'unit_cost_after' => (string) $cost,
                    'quantity_after' => (string) $quantity,
                    'colour_id' => $colourId,
                    'entry_group' => $group,
                ]);
            }
        }

        foreach ($rows->keys()->diff(array_keys($submitted)) as $colourId) {
            $this->stock->removeColour($material, (int) $colourId);
        }

        return $material->load(['category', 'unit', 'colours.colour']);
    }

    /**
     * A colour left out of the save goes only when its row is at 0 and no stock movement names it (AC-8).
     *
     * @param  list<int>  $keptIds
     *
     * @throws ValidationException one sentence per colour that stays, in the order of Settings › Colours
     */
    private function refuseRemovals(Material $material, array $keptIds): void
    {
        $left = $material->colours->reject(fn (MaterialColour $row): bool => in_array($row->colour_id, $keptIds, true));

        if ($left->isEmpty()) {
            return;
        }

        $history = StockMovement::query()
            ->where('material_id', $material->id)
            ->whereIn('colour_id', $left->pluck('colour_id'))
            ->distinct()
            ->pluck('colour_id')
            ->all();

        $sentences = $left
            ->map(fn (MaterialColour $row): ?string => $material->colourRemovalRefusal($row, in_array($row->colour_id, $history, true)))
            ->filter();

        if ($sentences->isNotEmpty()) {
            throw ValidationException::withMessages(['colours' => $sentences->implode(' ')]);
        }
    }

    /** A `correction` for one colour, before and after. */
    private function logQuantity(Material $material, int $colourId, BigDecimal $before, BigDecimal $after, string $group, User $actor): void
    {
        if ($before->isEqualTo($after)) {
            return;
        }

        $this->appendMovement->handle($material, StockMovementType::Correction, today()->toDateString(), $actor, [
            'quantity_before' => (string) $before,
            'quantity_after' => (string) $after,
            'colour_id' => $colourId,
            'entry_group' => $group,
        ]);
    }
}
