<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Rebuilds the stock balance of every material from its ledger and compares it with the cached `quantity_on_hand`
 * (ARCHITECTURE §4, ADR-0010). Read-only.
 *
 *     ledger balance = opening stock of the `created` entry
 *                    + the quantity of every `restock`
 *                    + (after - before) of every `correction`, `colour_split` and `colour_join`
 *                    - the quantity of every `cutting`                   (S-53: materials taken out of stock)
 *                    + the quantity of every `cutting_return`            (materials given back)
 *
 * A `price_change` moves no stock.
 *
 * A material kept per colour (INV-21, ADR-0029 section 7) is checked on three invariants, each reported as its own drift:
 * - the entries **without a colour** add up to the one stock (`quantity_on_hand` of a one-stock material, 0 while per colour);
 * - the entries **of colour c** add up to that colour's row (0 once the row is gone, so a switched-back material is checked too);
 * - **all** entries add up to `quantity_on_hand` (reported only when the two above hold: a drifting colour drifts the total too).
 * For a one-stock material the first is the classic check, reported under the material's own name.
 *
 * Exact maths: the sums are BigDecimal in PHP, never SQL `SUM` (SQLite would add decimals as floats). Soft-deleted materials
 * are checked too: their stock is still stored. A material with no `created` entry has an opening stock of zero, so one that
 * holds stock without a ledger drifts.
 *
 * @phpstan-type Drift array{material_id: int, sku: string, name: string, stored: string, ledger: string, drift: string, colour_id: int|null}
 * @phpstan-type Balance array{all: BigDecimal, none: BigDecimal, colours: array<int, BigDecimal>}
 */
final class StockLedgerVerifier
{
    /** Materials loaded per query, so memory stays flat however large the catalogue grows. */
    private const CHUNK_SIZE = 500;

    /**
     * A drift of a colour has its `colour_id`, and its `name` reads "<material> · <colour>"; one of the entries without a colour of a
     * per-colour material reads "<material> · no colour".
     *
     * @return array{checked: int, drifts: list<Drift>}
     */
    public function verify(): array
    {
        $checked = 0;
        $drifts = [];

        Material::query()
            ->withTrashed()
            ->without(['category', 'unit'])
            ->select(['id', 'sku', 'name', 'quantity_on_hand', 'stock_mode'])
            ->chunkById(self::CHUNK_SIZE, function ($materials) use (&$checked, &$drifts): void {
                $ids = array_values(array_map(static fn (Material $material): int => $material->id, $materials->all()));
                $balances = $this->ledgerBalances($ids);
                $rows = $this->colourRows($ids);

                foreach ($materials as $material) {
                    $checked++;
                    array_push($drifts, ...$this->driftsOf($material, $balances[$material->id] ?? null, $rows[$material->id] ?? []));
                }
            });

        return ['checked' => $checked, 'drifts' => $this->withColourNames($drifts)];
    }

    /**
     * @param  Balance|null  $balance  null for a material with no entry
     * @param  array<int, string>  $rows  the quantity of each colour row, by colour id
     * @return list<Drift>
     */
    private function driftsOf(Material $material, ?array $balance, array $rows): array
    {
        $perColour = $material->stock_mode === MaterialStockMode::PerColour;
        $stored = BigDecimal::of($material->quantity_on_hand);

        $drifts = [$this->drift($material, $perColour ? ' · no colour' : '', null, $perColour ? BigDecimal::zero() : $stored, $balance['none'] ?? BigDecimal::zero())];

        foreach (array_unique([...array_keys($rows), ...array_keys($balance['colours'] ?? [])]) as $colourId) {
            $drifts[] = $this->drift($material, '', $colourId, BigDecimal::of($rows[$colourId] ?? '0'), $balance['colours'][$colourId] ?? BigDecimal::zero());
        }

        $drifts = array_values(array_filter($drifts));

        if ($drifts !== []) {
            return $drifts;
        }

        $total = $this->drift($material, '', null, $stored, $balance['all'] ?? BigDecimal::zero());

        return $total === null ? [] : [$total];
    }

    /** @return Drift|null null when the stored and the ledger figures agree */
    private function drift(Material $material, string $suffix, ?int $colourId, BigDecimal $stored, BigDecimal $ledger): ?array
    {
        if ($stored->isEqualTo($ledger)) {
            return null;
        }

        return [
            'material_id' => $material->id,
            'sku' => $material->sku,
            'name' => $material->name.$suffix,
            'stored' => (string) $stored->toScale(4, RoundingMode::HalfUp),
            'ledger' => (string) $ledger->toScale(4, RoundingMode::HalfUp),
            'drift' => (string) $stored->minus($ledger)->toScale(4, RoundingMode::HalfUp),
            'colour_id' => $colourId,
        ];
    }

    /**
     * Names the colour of each colour drift (one query, only when one exists).
     *
     * @param  list<Drift>  $drifts
     * @return list<Drift>
     */
    private function withColourNames(array $drifts): array
    {
        $ids = array_values(array_unique(array_filter(array_column($drifts, 'colour_id'))));

        if ($ids === []) {
            return $drifts;
        }

        $names = Colour::query()->whereIn('id', $ids)->pluck('name', 'id');

        return array_map(static function (array $drift) use ($names): array {
            if ($drift['colour_id'] !== null) {
                $drift['name'] .= ' · '.$names->get($drift['colour_id'], "colour #{$drift['colour_id']}");
            }

            return $drift;
        }, $drifts);
    }

    /**
     * @param  list<int>  $materialIds
     * @return array<int, array<int, string>> the quantity of each colour row, by material id and colour id
     */
    private function colourRows(array $materialIds): array
    {
        $rows = [];

        foreach (MaterialColour::query()->whereIn('material_id', $materialIds)->get(['material_id', 'colour_id', 'quantity_on_hand']) as $row) {
            $rows[$row->material_id][$row->colour_id] = $row->quantity_on_hand;
        }

        return $rows;
    }

    /**
     * @param  list<int>  $materialIds
     * @return array<int, Balance> the ledger balances of each material that has entries, by material id
     */
    private function ledgerBalances(array $materialIds): array
    {
        $balances = [];

        $movements = StockMovement::query()
            ->whereIn('material_id', $materialIds)
            ->select(['id', 'material_id', 'colour_id', 'type', 'quantity', 'quantity_before', 'quantity_after'])
            ->orderBy('id')
            ->cursor();

        foreach ($movements as $movement) {
            $balance = $balances[$movement->material_id] ?? ['all' => BigDecimal::zero(), 'none' => BigDecimal::zero(), 'colours' => []];
            $change = $this->change($movement);

            $balance['all'] = $balance['all']->plus($change);

            if ($movement->colour_id === null) {
                $balance['none'] = $balance['none']->plus($change);
            } else {
                $balance['colours'][$movement->colour_id] = ($balance['colours'][$movement->colour_id] ?? BigDecimal::zero())->plus($change);
            }

            $balances[$movement->material_id] = $balance;
        }

        return $balances;
    }

    /** How much one entry moves the stock. */
    private function change(StockMovement $movement): BigDecimal
    {
        return match ($movement->type) {
            StockMovementType::Created => BigDecimal::of($movement->quantity ?? $movement->quantity_after ?? '0'),
            StockMovementType::Restock => BigDecimal::of($movement->quantity ?? '0'),
            StockMovementType::Correction,
            StockMovementType::ColourSplit,
            StockMovementType::ColourJoin => BigDecimal::of($movement->quantity_after ?? '0')->minus($movement->quantity_before ?? '0'),
            StockMovementType::PriceChange => BigDecimal::zero(),
            StockMovementType::Cutting => BigDecimal::of($movement->quantity ?? '0')->negated(),
            StockMovementType::CuttingReturn => BigDecimal::of($movement->quantity ?? '0'),
        };
    }
}
