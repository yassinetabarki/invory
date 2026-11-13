<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Material;
use App\Models\MaterialColour;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The only writer of a material's quantities and costs (INV-21, ADR-0029 section 3). A one-stock material has its two columns written
 * directly; a per-colour material has its `material_colours` rows written and then `materials.quantity_on_hand` set to their exact total
 * and, with cost per colour, `materials.unit_cost` to the lowest colour cost. The colours' own rows are never written anywhere else.
 *
 * Every call needs an open transaction and works on the material row locked by {@see self::lock()}, which guards its colour rows
 * too: call `lock()` first, then write. The callers write the stock movements themselves. Colour rows are read and written through
 * `MaterialColour` by material id, never through the ordered `Material::colours()` relation (it joins `colours`); after a write the
 * model's `colours` relation is loaded again, in Settings › Colours order.
 *
 * Switching the modes (INV-21-g) is {@see self::switchMode()}: it moves `stock_mode` / `cost_mode` and the colour rows together; the modes are
 * read from the model at each call, and {@see self::sync()} is where the total and the lowest cost follow them.
 */
final readonly class MaterialStockWriter
{
    public function __construct(private LockMaterialForUpdate $lockMaterial) {}

    /** The entry point the Action architecture rule asks for: it takes the lock, like {@see self::lock()}. */
    public function handle(Material $material): Material
    {
        return $this->lock($material);
    }

    /**
     * Reads the material again under the row lock and loads its `colours` (none for a one-stock material), so every figure that follows starts
     * from what is really stored.
     */
    public function lock(Material $material): Material
    {
        $this->assertTransaction();
        $this->lockMaterial->handle($material);

        if ($material->stock_mode === MaterialStockMode::Single) {
            $material->setRelation('colours', $material->newCollection());
        } else {
            $material->load('colours');
        }

        return $material;
    }

    /** Sets the one stock (`$colourId` null, a one-stock material) or one colour's quantity. */
    public function setQuantity(Material $material, ?int $colourId, BigDecimal|string $quantity): void
    {
        $this->change($material, $colourId, fn (): BigDecimal => $this->scaled($quantity));
    }

    /** Adds a signed amount to the one stock or one colour's quantity; a result below 0 is refused. */
    public function addQuantity(Material $material, ?int $colourId, BigDecimal|string $delta): void
    {
        $this->change($material, $colourId, fn (BigDecimal $current): BigDecimal => $current->plus($this->scaled($delta)));
    }

    /** Sets the unit cost of a one-stock material, or the same cost of every colour. */
    public function setCost(Material $material, BigDecimal|string $unitCost): void
    {
        $this->assertTransaction();

        if ($material->cost_mode === MaterialCostMode::PerColour) {
            throw new LogicException("Material #{$material->id} has a cost per colour: set a colour's cost.");
        }

        $material->unit_cost = (string) $this->nonNegative($this->scaled($unitCost), 'unit cost');
        $material->save();
    }

    /** Sets one colour's unit cost (cost per colour only); the material's own cost follows as the lowest. */
    public function setColourCost(Material $material, int $colourId, BigDecimal|string $unitCost): void
    {
        $this->assertTransaction();

        if ($material->cost_mode !== MaterialCostMode::PerColour) {
            throw new LogicException("Material #{$material->id} has the same cost for every colour.");
        }

        $row = $this->colourRow($material, $colourId);
        $row->unit_cost = (string) $this->nonNegative($this->scaled($unitCost), 'unit cost');
        $row->save();

        $this->sync($material);
    }

    /** Adds a colour row; with cost per colour it needs a cost, with the same cost it must have none. */
    public function addColour(Material $material, int $colourId, BigDecimal|string $quantity, BigDecimal|string|null $unitCost): MaterialColour
    {
        $this->assertTransaction();

        if ($material->stock_mode !== MaterialStockMode::PerColour) {
            throw new LogicException("Material #{$material->id} is one stock: it has no colours.");
        }

        if (($unitCost !== null) !== ($material->cost_mode === MaterialCostMode::PerColour)) {
            throw new LogicException("Material #{$material->id}: a colour has a cost exactly when the cost is per colour.");
        }

        $row = new MaterialColour;
        $row->forceFill([
            'workshop_id' => $material->workshop_id,
            'material_id' => $material->id,
            'colour_id' => $colourId,
            'quantity_on_hand' => (string) $this->nonNegative($this->scaled($quantity), 'quantity'),
            'unit_cost' => $unitCost === null ? null : (string) $this->nonNegative($this->scaled($unitCost), 'unit cost'),
        ])->save();

        $this->sync($material);

        return $row;
    }

    /** Deletes a colour row, whatever it holds: the rules for when a colour may leave are the caller's (INV-21-f). */
    public function removeColour(Material $material, int $colourId): void
    {
        $this->assertTransaction();

        $this->colourRow($material, $colourId)->delete();

        $this->sync($material);
    }

    /**
     * Moves a material to another stock mode and/or cost mode (INV-21-g); the caller writes the ledger entries.
     *
     * - one stock to per colour: the material has no colour row and no quantity until `addColour()` gives it some (its cost stays);
     * - per colour to one stock: the colour rows go, their exact total becomes the one stock, `$unitCost` (default: the cost it has) the
     *   one cost; returns that total;
     * - cost per colour on: every colour row takes the material's cost, to be changed with `setColourCost()`;
     * - cost per colour off: the rows lose their cost and `$unitCost` (required) is the same cost of every colour.
     *
     * @return BigDecimal the quantity the material holds once switched (the total of the colour rows when they went)
     */
    public function switchMode(Material $material, MaterialStockMode $stockMode, MaterialCostMode $costMode, BigDecimal|string|null $unitCost = null): BigDecimal
    {
        $this->assertTransaction();

        if ($stockMode === MaterialStockMode::Single && $costMode !== MaterialCostMode::Same) {
            throw new LogicException("Material #{$material->id}: one stock always has the same cost.");
        }

        $wasPerColour = $material->stock_mode === MaterialStockMode::PerColour;
        $total = BigDecimal::of($material->quantity_on_hand);

        if ($wasPerColour && $stockMode === MaterialStockMode::Single) {
            $total = BigDecimal::zero();

            foreach (MaterialColour::query()->where('material_id', $material->id)->get() as $row) {
                $total = $total->plus($row->quantity_on_hand);
                $row->delete();
            }

            $material->quantity_on_hand = (string) $total->toScale(4, RoundingMode::HalfUp);
        } elseif (! $wasPerColour && $stockMode === MaterialStockMode::PerColour) {
            $total = BigDecimal::zero();
            $material->quantity_on_hand = '0.0000';
        } elseif ($costMode === MaterialCostMode::PerColour && $material->cost_mode !== MaterialCostMode::PerColour) {
            MaterialColour::query()->where('material_id', $material->id)->update(['unit_cost' => $material->unit_cost]);
        } elseif ($costMode === MaterialCostMode::Same && $material->cost_mode !== MaterialCostMode::Same) {
            MaterialColour::query()->where('material_id', $material->id)->update(['unit_cost' => null]);
        }

        if ($unitCost !== null && $costMode === MaterialCostMode::Same) {
            $material->unit_cost = (string) $this->nonNegative($this->scaled($unitCost), 'unit cost');
        }

        $material->stock_mode = $stockMode;
        $material->cost_mode = $costMode;
        $material->save();

        if ($stockMode === MaterialStockMode::PerColour) {
            $material->load('colours');
        } else {
            $material->setRelation('colours', $material->newCollection());
        }

        return $total;
    }

    /**
     * @param  Closure(BigDecimal): BigDecimal  $next  the new quantity, from the current one
     */
    private function change(Material $material, ?int $colourId, Closure $next): void
    {
        $this->assertTransaction();

        if ($colourId === null) {
            if ($material->stock_mode !== MaterialStockMode::Single) {
                throw new LogicException("Material #{$material->id} is kept per colour: name the colour.");
            }

            $material->quantity_on_hand = (string) $this->nonNegative($next(BigDecimal::of($material->quantity_on_hand)), 'quantity');
            $material->save();

            return;
        }

        $row = $this->colourRow($material, $colourId);
        $row->quantity_on_hand = (string) $this->nonNegative($next(BigDecimal::of($row->quantity_on_hand)), 'quantity');
        $row->save();

        $this->sync($material);
    }

    /** Saves the total of the colour rows as the material's quantity (and, with cost per colour, their lowest cost as its unit cost). */
    private function sync(Material $material): void
    {
        $total = BigDecimal::zero();
        $lowest = null;

        foreach (MaterialColour::query()->where('material_id', $material->id)->get() as $row) {
            $total = $total->plus($row->quantity_on_hand);

            if ($row->unit_cost !== null && ($lowest === null || BigDecimal::of($row->unit_cost)->isLessThan($lowest))) {
                $lowest = BigDecimal::of($row->unit_cost);
            }
        }

        $material->quantity_on_hand = (string) $total->toScale(4, RoundingMode::HalfUp);

        if ($material->cost_mode === MaterialCostMode::PerColour && $lowest !== null) {
            $material->unit_cost = (string) $lowest->toScale(4, RoundingMode::HalfUp);
        }

        $material->save();
        $material->load('colours');
    }

    private function colourRow(Material $material, int $colourId): MaterialColour
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour) {
            throw new LogicException("Material #{$material->id} is one stock: it has no colours.");
        }

        return MaterialColour::query()->where('material_id', $material->id)->where('colour_id', $colourId)->first()
            ?? throw new LogicException("Material #{$material->id} has no stock of colour #{$colourId}.");
    }

    private function scaled(BigDecimal|string $value): BigDecimal
    {
        return BigDecimal::of($value)->toScale(4, RoundingMode::HalfUp);
    }

    private function nonNegative(BigDecimal $value, string $what): BigDecimal
    {
        return $value->isNegative() ? throw new LogicException("A {$what} is never below 0.") : $value;
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('MaterialStockWriter needs an open transaction: wrap the write in DB::transaction().');
        }
    }
}
