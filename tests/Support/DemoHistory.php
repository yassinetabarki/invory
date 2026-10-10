<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\Materials\AppendStockMovement;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The stock history the Phase 2 preview fixture used to give the 12 demo materials (docs/plans/phase-2-ui-first-preview.md), built
 * through the real writer of the ledger, {@see AppendStockMovement}, for the tests that need a believable history. The stock
 * the DemoCatalogSeeder gives each material is the balance this history ends on, so the ledger of every demo material balances.
 *
 * Entries are appended in the order below (so within a day a later entry has a higher id) and dated `days_ago` before today. The
 * ids are NOT fixed (a PostgreSQL sequence never goes back): tests compare order and values, not numbers.
 *
 * @phpstan-type Entry array{
 *     days_ago: int,
 *     type: StockMovementType,
 *     values: array<string, string>,
 *     reference?: string,
 *     note?: string,
 * }
 */
final class DemoHistory
{
    /** Who did every entry. */
    public const USER_NAME = 'Admin';

    /**
     * Entries by material SKU.
     *
     * @return array<string, list<Entry>>
     */
    public static function entries(): array
    {
        $created = fn (string $quantity, string $price): array => ['days_ago' => 37, 'type' => StockMovementType::Created, 'values' => ['quantity' => $quantity, 'quantity_after' => $quantity, 'unit_price' => $price]];
        $restock = fn (int $daysAgo, string $quantity, string $after, string $price, ?string $reference = null): array => ['days_ago' => $daysAgo, 'type' => StockMovementType::Restock, 'values' => ['quantity' => $quantity, 'quantity_after' => $after, 'unit_price' => $price, 'reference' => $reference]];
        $correction = fn (int $daysAgo, string $before, string $after, string $note): array => ['days_ago' => $daysAgo, 'type' => StockMovementType::Correction, 'values' => ['quantity_before' => $before, 'quantity_after' => $after, 'note' => $note]];
        $priceChange = fn (int $daysAgo, string $before, string $after, string $stock): array => ['days_ago' => $daysAgo, 'type' => StockMovementType::PriceChange, 'values' => ['unit_cost_before' => $before, 'unit_cost_after' => $after, 'quantity_after' => $stock]];

        return [
            'MAT-RVT-009' => [$created('3000', '0.0800'), $restock(20, '2000', '5000', '0.0800', 'PO-2270')],
            'MAT-CAN-010' => [$created('180', '5.4000'), $restock(16, '120', '300', '5.4000')],
            'MAT-JRS-180' => [$created('120', '4.2000'), $correction(13, '120', '64', 'Stock count')],
            'MAT-DEN-012' => [$created('150', '8.5000'), $restock(18, '100', '250', '8.5000', 'PO-2281')],
            'MAT-BTN-017' => [
                $created('600', '0.3200'),
                $correction(24, '600', '400', 'Stock count'),
                $restock(6, '500', '900', '0.3500', 'PO-2291'),
                $priceChange(6, '0.3200', '0.3500', '900'),
            ],
            'MAT-ZIP-018' => [$created('750', '0.9500'), $restock(22, '500', '1250', '0.9500')],
            'MAT-LTH-NAP' => [$created('340', '6.8000'), $restock(10, '200', '540', '6.8000', 'PO-2286')],
            'MAT-THR-PES' => [$created('10000', '0.0040'), $restock(28, '10000', '20000', '0.0040')],
            'MAT-SOL-042' => [$created('60', '5.9000'), $correction(8, '60', '0', 'Used in production')],
            'MAT-LIN-VIS' => [$created('180', '3.1000')],
            'MAT-LAC-120' => [$created('400', '0.4500'), $restock(26, '200', '600', '0.4500')],
            'MAT-LBL-WOV' => [
                $created('250', '0.1000'),
                $restock(14, '150', '400', '0.1200'),
                $priceChange(14, '0.1000', '0.1200', '400'),
            ],
        ];
    }

    /**
     * Writes the whole history of the demo materials, in fixture order. The materials must exist (DemoCatalogSeeder). Returns the
     * user that did it (the given one, or a new "Admin").
     */
    public static function seed(?User $actor = null): User
    {
        $actor ??= User::factory()->create(['name' => self::USER_NAME]);
        $append = app(AppendStockMovement::class);
        $today = CarbonImmutable::today();

        foreach (self::entries() as $sku => $entries) {
            $material = Material::query()->where('sku', $sku)->firstOrFail();

            foreach ($entries as $entry) {
                $append->handle($material, $entry['type'], $today->subDays($entry['days_ago'])->toDateString(), $actor, $entry['values']);
            }
        }

        return $actor;
    }

    /**
     * The ids of the ledger rows of a material, oldest entry first (entry order).
     *
     * @return list<int>
     */
    public static function idsOf(Material $material): array
    {
        return StockMovement::query()->where('material_id', $material->id)->orderBy('id')->pluck('id')->all();
    }
}
