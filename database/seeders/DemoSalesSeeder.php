<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\ResolvesDemoWorkshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 60 demo sales of the approved mockup (docs/plans/phase-2-ui-first-preview.md, "Preview fixture") as real rows, dated
 * relative to today so the "last 30 days" figures of the costing domain skill hold whenever it runs (48 sales, 296 units,
 * revenue 16850.0000 over the last 30 days).
 *
 * Self-contained data (no dependency on the preview layer). Sales have no natural key, so it inserts ONLY when the demo
 * workshop has no sale yet: running it again, or after the user recorded or deleted sales, changes nothing. A sale whose demo product is
 * missing (never seeded, or soft-deleted) is skipped. `user_id` stays null: nobody recorded them. Stock is untouched (decision Q8).
 */
class DemoSalesSeeder extends Seeder
{
    use ResolvesDemoWorkshop;

    /**
     * The unit price of a sale is the price its product was sold at (the demo selling price), so editing a product's selling
     * price later doesn't rewrite this history.
     *
     * @var array<string, string>
     */
    public const PRICES = [
        'PRD-TSH-002' => '24.0000',
        'PRD-JNS-005' => '79.0000',
        'PRD-JKT-001' => '89.0000',
        'PRD-BLT-007' => '45.0000',
        'PRD-TOT-004' => '249.0000',
        'PRD-SNK-003' => '69.0000',
    ];

    /**
     * Oldest first, so ids grow with the date: [days before today, product SKU, quantity, note].
     *
     * @var list<array{0: int, 1: string, 2: int, 3: string|null}>
     */
    public const SALES = [
        [150, 'PRD-TSH-002', 30, 'Wholesale order'], [138, 'PRD-JNS-005', 12, 'Wholesale order'], [121, 'PRD-JKT-001', 6, 'Showroom'],
        [110, 'PRD-BLT-007', 10, 'Boutique Lyon'], [96, 'PRD-TOT-004', 3, 'Showroom'], [84, 'PRD-TSH-002', 25, 'Web shop'],
        [73, 'PRD-SNK-003', 6, 'Web shop'], [61, 'PRD-JNS-005', 9, 'Boutique Lyon'], [52, 'PRD-JKT-001', 7, 'Wholesale order'],
        [44, 'PRD-TSH-002', 18, 'Web shop'], [38, 'PRD-BLT-007', 5, null], [33, 'PRD-TOT-004', 2, 'Showroom'],
        [29, 'PRD-TSH-002', 10, 'Web shop'], [29, 'PRD-BLT-007', 4, 'Boutique Lyon'], [28, 'PRD-JNS-005', 5, 'Wholesale order'],
        [27, 'PRD-JKT-001', 3, 'Web shop'], [27, 'PRD-TOT-004', 1, 'Showroom'], [26, 'PRD-JNS-005', 5, 'Web shop'],
        [26, 'PRD-TSH-002', 8, null], [25, 'PRD-BLT-007', 5, 'Web shop'], [24, 'PRD-JNS-005', 7, 'Boutique Lyon'],
        [24, 'PRD-SNK-003', 3, 'Web shop'], [23, 'PRD-TSH-002', 12, 'Wholesale order'], [22, 'PRD-JKT-001', 6, 'Showroom'],
        [21, 'PRD-BLT-007', 3, 'Boutique Lyon'], [21, 'PRD-TOT-004', 3, 'Web shop'], [20, 'PRD-TSH-002', 14, 'Web shop'],
        [20, 'PRD-JNS-005', 6, 'Web shop'], [19, 'PRD-BLT-007', 6, 'Wholesale order'], [18, 'PRD-JKT-001', 4, 'Web shop'],
        [18, 'PRD-TSH-002', 6, 'Showroom'], [17, 'PRD-JNS-005', 8, 'Wholesale order'], [17, 'PRD-SNK-003', 1, null],
        [16, 'PRD-TOT-004', 2, 'Boutique Lyon'], [16, 'PRD-BLT-007', 2, 'Web shop'], [15, 'PRD-TSH-002', 10, 'Web shop'],
        [15, 'PRD-JNS-005', 4, 'Showroom'], [14, 'PRD-JKT-001', 5, 'Wholesale order'], [14, 'PRD-BLT-007', 4, 'Web shop'],
        [13, 'PRD-JKT-001', 5, 'Web shop'], [13, 'PRD-TSH-002', 15, 'Wholesale order'], [13, 'PRD-TOT-004', 2, 'Web shop'],
        [12, 'PRD-JKT-001', 6, 'Wholesale order'], [11, 'PRD-JNS-005', 10, 'Boutique Lyon'], [11, 'PRD-TSH-002', 10, 'Web shop'],
        [10, 'PRD-BLT-007', 4, 'Showroom'], [10, 'PRD-SNK-003', 3, 'Web shop'], [9, 'PRD-TSH-002', 10, 'Showroom'],
        [9, 'PRD-TOT-004', 2, 'Web shop'], [9, 'PRD-JNS-005', 5, 'Web shop'], [8, 'PRD-TSH-002', 15, 'Web shop'],
        [7, 'PRD-JKT-001', 5, 'Showroom'], [6, 'PRD-JNS-005', 8, 'Wholesale order'], [5, 'PRD-SNK-003', 2, 'Web shop'],
        [4, 'PRD-TSH-002', 12, 'Wholesale order'], [3, 'PRD-BLT-007', 3, 'Boutique Lyon'], [3, 'PRD-JKT-001', 4, 'Web shop'],
        [2, 'PRD-TOT-004', 2, 'Showroom'], [1, 'PRD-TSH-002', 20, 'Web shop'], [1, 'PRD-JNS-005', 6, 'Boutique Lyon'],
    ];

    public function run(): void
    {
        $workshopId = $this->demoWorkshopId();

        if (DB::table('sales')->where('workshop_id', $workshopId)->exists()) {
            return;
        }

        $products = Product::query()->where('workshop_id', $workshopId)->whereIn('sku', array_keys(self::PRICES))->pluck('id', 'sku');
        $today = CarbonImmutable::today();
        $now = now();
        $rows = [];

        foreach (self::SALES as [$daysAgo, $sku, $quantity, $note]) {
            if (! isset($products[$sku])) {
                continue;
            }

            $rows[] = [
                'workshop_id' => $workshopId,
                'product_id' => $products[$sku],
                'quantity' => $quantity,
                'unit_price' => self::PRICES[$sku],
                'sold_on' => $today->subDays($daysAgo)->toDateString(),
                'note' => $note,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sales')->insert($rows);
    }
}
