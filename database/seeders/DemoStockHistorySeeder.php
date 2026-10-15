<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Materials\AppendStockMovement;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\ResolvesDemoWorkshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The stock history of the 12 demo materials of {@see DemoCatalogSeeder}: every one gets its "Created" entry, and three of them
 * (the button, the label and the denim) also a restock and the price change that came with it, so the history screen and
 * `php artisan invory:stock:verify` have something real to show. The history ends on the stock and cost the catalogue seeder
 * gives each material, so the ledger balances. Dates are relative to today (37 days ago for "Created").
 *
 * Writes only through {@see AppendStockMovement}, the one writer of the ledger, never through the restock Action: the
 * catalogue already holds the final stock and cost, so replaying real restocks would double them.
 *
 * Idempotent and never destructive: a material that is missing (or deleted) is skipped, and so is one that already has any
 * ledger row, so running it again writes nothing and a real history is left alone. The actor is the demo user
 * `test@example.com`, which `DatabaseSeeder` creates first; the ledger needs a user, so without it nothing is written.
 *
 * Not called from {@see DemoCatalogSeeder}: tests seed the catalogue and then `Tests\Support\DemoHistory`, which would
 * give every material two "Created" rows.
 */
final class DemoStockHistorySeeder extends Seeder
{
    use ResolvesDemoWorkshop;

    /** The user every entry is credited to. */
    public const ACTOR_EMAIL = 'test@example.com';

    /** Days between the "Created" entry and today. */
    private const CREATED_DAYS_AGO = 37;

    /**
     * Per material SKU, all 12 of the catalogue. `opening` is the opening stock and cost of the "Created" entry; `null` means the
     * catalogue's own stock and cost (a material with no further history). `restock` adds a restock `days_ago` before today,
     * `after` being the stock then; when its `price` differs from the opening cost, a `price_change` follows the same day.
     *
     * @var array<string, array{
     *     opening: array{quantity: string, price: string}|null,
     *     restock?: array{days_ago: int, quantity: string, after: string, price: string, reference: string|null},
     * }>
     */
    public const HISTORY = [
        'MAT-RVT-009' => ['opening' => null],
        'MAT-CAN-010' => ['opening' => null],
        'MAT-JRS-180' => ['opening' => null],
        'MAT-DEN-012' => [
            'opening' => ['quantity' => '150', 'price' => '8.2000'],
            'restock' => ['days_ago' => 18, 'quantity' => '100', 'after' => '250', 'price' => '8.5000', 'reference' => 'PO-2281'],
        ],
        'MAT-BTN-017' => [
            'opening' => ['quantity' => '400', 'price' => '0.3200'],
            'restock' => ['days_ago' => 6, 'quantity' => '500', 'after' => '900', 'price' => '0.3500', 'reference' => 'PO-2291'],
        ],
        'MAT-ZIP-018' => ['opening' => null],
        'MAT-LTH-NAP' => ['opening' => null],
        'MAT-THR-PES' => ['opening' => null],
        'MAT-SOL-042' => ['opening' => null],
        'MAT-LIN-VIS' => ['opening' => null],
        'MAT-LAC-120' => ['opening' => null],
        'MAT-LBL-WOV' => [
            'opening' => ['quantity' => '250', 'price' => '0.1000'],
            'restock' => ['days_ago' => 14, 'quantity' => '150', 'after' => '400', 'price' => '0.1200', 'reference' => null],
        ],
    ];

    public function run(AppendStockMovement $append): void
    {
        $actor = User::query()->where('email', self::ACTOR_EMAIL)->first();

        if ($actor === null) {
            return;
        }

        $seeded = array_column(DemoCatalogSeeder::MATERIALS, null, 'sku');
        $materials = Material::query()->without('category', 'unit')->where('workshop_id', $this->demoWorkshopId())
            ->whereIn('sku', array_keys(self::HISTORY))->get()->keyBy('sku');
        $today = CarbonImmutable::today();

        foreach (self::HISTORY as $sku => $history) {
            $material = $materials->get($sku);

            if ($material === null || $material->stockMovements()->exists()) {
                continue;
            }

            $opening = $history['opening'] ?? ['quantity' => $seeded[$sku]['quantity_on_hand'], 'price' => $seeded[$sku]['unit_cost']];

            DB::transaction(function () use ($append, $material, $actor, $today, $history, $opening): void {
                $append->handle($material, StockMovementType::Created, $today->subDays(self::CREATED_DAYS_AGO)->toDateString(), $actor, [
                    'quantity' => $opening['quantity'],
                    'quantity_after' => $opening['quantity'],
                    'unit_price' => $opening['price'],
                ]);

                $restock = $history['restock'] ?? null;

                if ($restock === null) {
                    return;
                }

                $date = $today->subDays($restock['days_ago'])->toDateString();

                $append->handle($material, StockMovementType::Restock, $date, $actor, [
                    'quantity' => $restock['quantity'],
                    'quantity_after' => $restock['after'],
                    'unit_price' => $restock['price'],
                    'reference' => $restock['reference'],
                ]);

                if ($restock['price'] !== $opening['price']) {
                    $append->handle($material, StockMovementType::PriceChange, $date, $actor, [
                        'unit_cost_before' => $opening['price'],
                        'unit_cost_after' => $restock['price'],
                        'quantity_after' => $restock['after'],
                    ]);
                }
            });
        }
    }
}
