<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Rebuilds the stock balance of every material from its ledger and compares it with the cached `quantity_on_hand`
 * (ARCHITECTURE §4, ADR-0010). Read-only.
 *
 *     ledger balance = opening stock of the `created` entry
 *                    + the quantity of every `restock`
 *                    + (after - before) of every `correction`           a `price_change` moves no stock
 *
 * Exact maths: the sums are BigDecimal in PHP, never SQL `SUM` (SQLite would add decimals as floats). Soft-deleted materials
 * are checked too: their stock is still stored. A material with no `created` entry has an opening stock of zero, so one that
 * holds stock without a ledger drifts.
 *
 * @phpstan-type Drift array{material_id: int, sku: string, name: string, stored: string, ledger: string, drift: string}
 */
final class StockLedgerVerifier
{
    /** Materials loaded per query, so memory stays flat however large the catalogue grows. */
    private const CHUNK_SIZE = 500;

    /**
     * @return array{checked: int, drifts: list<Drift>}
     */
    public function verify(): array
    {
        $checked = 0;
        $drifts = [];

        Material::query()
            ->withTrashed()
            ->without(['category', 'unit'])
            ->select(['id', 'sku', 'name', 'quantity_on_hand'])
            ->chunkById(self::CHUNK_SIZE, function ($materials) use (&$checked, &$drifts): void {
                $balances = $this->ledgerBalances($materials->pluck('id')->all());

                foreach ($materials as $material) {
                    $checked++;
                    $ledger = $balances[$material->id] ?? BigDecimal::zero();
                    $stored = BigDecimal::of($material->quantity_on_hand);

                    if (! $stored->isEqualTo($ledger)) {
                        $drifts[] = [
                            'material_id' => $material->id,
                            'sku' => $material->sku,
                            'name' => $material->name,
                            'stored' => (string) $stored->toScale(4, RoundingMode::HalfUp),
                            'ledger' => (string) $ledger->toScale(4, RoundingMode::HalfUp),
                            'drift' => (string) $stored->minus($ledger)->toScale(4, RoundingMode::HalfUp),
                        ];
                    }
                }
            });

        return ['checked' => $checked, 'drifts' => $drifts];
    }

    /**
     * @param  list<int>  $materialIds
     * @return array<int, BigDecimal> the ledger balance of each material that has entries, by material id
     */
    private function ledgerBalances(array $materialIds): array
    {
        $balances = [];

        $movements = StockMovement::query()
            ->whereIn('material_id', $materialIds)
            ->select(['id', 'material_id', 'type', 'quantity', 'quantity_before', 'quantity_after'])
            ->orderBy('id')
            ->cursor();

        foreach ($movements as $movement) {
            $balances[$movement->material_id] = ($balances[$movement->material_id] ?? BigDecimal::zero())->plus($this->change($movement));
        }

        return $balances;
    }

    /** How much one entry moves the stock. */
    private function change(StockMovement $movement): BigDecimal
    {
        return match ($movement->type) {
            StockMovementType::Created => BigDecimal::of($movement->quantity ?? $movement->quantity_after ?? '0'),
            StockMovementType::Restock => BigDecimal::of($movement->quantity ?? '0'),
            StockMovementType::Correction => BigDecimal::of($movement->quantity_after ?? '0')->minus($movement->quantity_before ?? '0'),
            StockMovementType::PriceChange => BigDecimal::zero(),
        };
    }
}
