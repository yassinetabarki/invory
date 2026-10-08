<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Enums\StockMovementType;

/**
 * The raw INPUTS of the UI-first preview: everything that has no table yet (products and their compositions,
 * selling prices, sales, stock history, photo swatches). Source: docs/plans/phase-2-ui-first-preview.md, "Preview fixture".
 *
 * Nothing here is derived: costs, capacities, margins, totals and rankings are computed on the server by the
 * production domain classes (CostingCalculator, MarginCalculator, SalesCalculator). Dates are `days_ago`, relative to today.
 * The 12 materials themselves are real rows seeded by DemoCatalogSeeder; products reference them by SKU.
 *
 * @phpstan-type FixtureLine array{sku: string, quantity: string, waste_percent: string, notes: string}
 * @phpstan-type FixtureProduct array{
 *     id: int,
 *     sku: string,
 *     name: string,
 *     category: ProductCategory,
 *     status: ProductStatus,
 *     labor_cost: string,
 *     overhead_cost: string,
 *     selling_price: string|null,
 *     description: string,
 *     lines: list<FixtureLine>,
 * }
 * @phpstan-type FixtureSale array{id: int, days_ago: int, product_id: int, quantity: int, note: string|null}
 * @phpstan-type FixtureMovement array{
 *     days_ago: int,
 *     type: StockMovementType,
 *     quantity?: string,
 *     quantity_before?: string,
 *     quantity_after?: string,
 *     unit_price?: string,
 *     unit_cost_before?: string,
 *     unit_cost_after?: string,
 *     reference?: string,
 *     note?: string,
 * }
 */
final class PreviewFixture
{
    /** Who did every history entry. */
    public const USER_NAME = 'Admin';

    /** Public folder of the static swatches, relative to `public/`. */
    public const PHOTO_DIRECTORY = 'mock/photos';

    /** Days ago the fixture products were created (the same day as the opening stock of the materials). */
    public const CREATED_DAYS_AGO = 37;

    /**
     * Photo swatches by SKU (materials and products): background tint and ink colour.
     * A SKU without an entry has no photo. The SVG files live in public/mock/photos/<lower-case sku>.svg.
     *
     * @var array<string, array{tint: string, ink: string}>
     */
    public const PHOTOS = [
        'MAT-RVT-009' => ['tint' => '#B8954A', 'ink' => '#FFFFFF'],
        'MAT-CAN-010' => ['tint' => '#D9CBB0', 'ink' => '#5B4A2A'],
        'MAT-JRS-180' => ['tint' => '#ECECE8', 'ink' => '#5F5F66'],
        'MAT-DEN-012' => ['tint' => '#3B4A7A', 'ink' => '#FFFFFF'],
        'MAT-BTN-017' => ['tint' => '#A88B4A', 'ink' => '#FFFFFF'],
        'MAT-ZIP-018' => ['tint' => '#B9BCC4', 'ink' => '#2A2A2E'],
        'MAT-LTH-NAP' => ['tint' => '#9A5B34', 'ink' => '#FFFFFF'],
        'MAT-THR-PES' => ['tint' => '#F4F1EA', 'ink' => '#5F5F66'],
        'MAT-SOL-042' => ['tint' => '#EFEDE6', 'ink' => '#5F5F66'],
        'MAT-LBL-WOV' => ['tint' => '#1F1F23', 'ink' => '#FFFFFF'],
        'PRD-JKT-001' => ['tint' => '#3B4A7A', 'ink' => '#FFFFFF'],
        'PRD-TSH-002' => ['tint' => '#ECECE8', 'ink' => '#5F5F66'],
        'PRD-SNK-003' => ['tint' => '#D9CBB0', 'ink' => '#5B4A2A'],
        'PRD-TOT-004' => ['tint' => '#9A5B34', 'ink' => '#FFFFFF'],
        'PRD-JNS-005' => ['tint' => '#2F3B66', 'ink' => '#FFFFFF'],
        'PRD-BLT-007' => ['tint' => '#7A4425', 'ink' => '#FFFFFF'],
    ];

    /**
     * The 7 fixture products, ids 1 to 7. Line quantities are in the material's own unit.
     *
     * @return list<FixtureProduct>
     */
    public static function products(): array
    {
        return [
            [
                'id' => 1,
                'sku' => 'PRD-JKT-001',
                'name' => 'Denim Jacket',
                'category' => ProductCategory::Apparel,
                'status' => ProductStatus::Active,
                'labor_cost' => '12.0000',
                'overhead_cost' => '3.5000',
                'selling_price' => '89.0000',
                'description' => 'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
                'lines' => [
                    ['sku' => 'MAT-DEN-012', 'quantity' => '1.8000', 'waste_percent' => '10.00', 'notes' => 'Body, sleeves, collar'],
                    ['sku' => 'MAT-BTN-017', 'quantity' => '8', 'waste_percent' => '0', 'notes' => 'Front placket (6), cuffs (2)'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '120', 'waste_percent' => '5.00', 'notes' => 'All seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Inside back yoke'],
                ],
            ],
            [
                'id' => 2,
                'sku' => 'PRD-TSH-002',
                'name' => 'Classic White T-shirt',
                'category' => ProductCategory::Apparel,
                'status' => ProductStatus::Active,
                'labor_cost' => '3.2000',
                'overhead_cost' => '0.7500',
                'selling_price' => '24.0000',
                'description' => 'Everyday crew-neck tee in soft 180gsm white cotton jersey.',
                'lines' => [
                    ['sku' => 'MAT-JRS-180', 'quantity' => '1.2000', 'waste_percent' => '6.00', 'notes' => 'Body and sleeves'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '80', 'waste_percent' => '5.00', 'notes' => 'All seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Neck'],
                ],
            ],
            [
                'id' => 3,
                'sku' => 'PRD-SNK-003',
                'name' => 'Canvas Low Sneaker',
                'category' => ProductCategory::Footwear,
                'status' => ProductStatus::Active,
                'labor_cost' => '9.3000',
                'overhead_cost' => '2.0000',
                'selling_price' => '69.0000',
                'description' => 'Low-top sneaker in natural cotton canvas on a vulcanised rubber cupsole.',
                'lines' => [
                    ['sku' => 'MAT-CAN-010', 'quantity' => '0.6500', 'waste_percent' => '10.00', 'notes' => 'Upper'],
                    ['sku' => 'MAT-SOL-042', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Outsole'],
                    ['sku' => 'MAT-LAC-120', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Laces'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '40', 'waste_percent' => '5.00', 'notes' => 'Upper seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Tongue'],
                ],
            ],
            [
                'id' => 4,
                'sku' => 'PRD-TOT-004',
                'name' => 'Leather Tote',
                'category' => ProductCategory::Bag,
                'status' => ProductStatus::Active,
                'labor_cost' => '22.5000',
                'overhead_cost' => '4.7700',
                'selling_price' => '249.0000',
                'description' => 'Structured tote in cognac nappa leather, viscose-lined with an inner zip pocket.',
                'lines' => [
                    ['sku' => 'MAT-LTH-NAP', 'quantity' => '9.2000', 'waste_percent' => '10.00', 'notes' => 'Body and handles'],
                    ['sku' => 'MAT-LIN-VIS', 'quantity' => '0.8000', 'waste_percent' => '5.00', 'notes' => 'Lining'],
                    ['sku' => 'MAT-ZIP-018', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Inner pocket'],
                    ['sku' => 'MAT-RVT-009', 'quantity' => '8', 'waste_percent' => '0', 'notes' => 'Handle anchors'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '150', 'waste_percent' => '5.00', 'notes' => 'All seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Inner pocket'],
                ],
            ],
            [
                'id' => 5,
                'sku' => 'PRD-JNS-005',
                'name' => 'Denim Jeans 5-pocket',
                'category' => ProductCategory::Apparel,
                'status' => ProductStatus::Active,
                'labor_cost' => '11.0000',
                'overhead_cost' => '2.9700',
                'selling_price' => '79.0000',
                'description' => 'Straight-leg five-pocket jeans in 12oz indigo denim with riveted pocket corners.',
                'lines' => [
                    ['sku' => 'MAT-DEN-012', 'quantity' => '1.4000', 'waste_percent' => '8.00', 'notes' => 'Body'],
                    ['sku' => 'MAT-BTN-017', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Waistband'],
                    ['sku' => 'MAT-RVT-009', 'quantity' => '6', 'waste_percent' => '0', 'notes' => 'Pocket corners'],
                    ['sku' => 'MAT-ZIP-018', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Fly'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '180', 'waste_percent' => '5.00', 'notes' => 'All seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Back waistband'],
                ],
            ],
            [
                'id' => 6,
                'sku' => 'PRD-JKT-006',
                'name' => 'Canvas Chore Jacket',
                'category' => ProductCategory::Apparel,
                'status' => ProductStatus::Draft,
                'labor_cost' => '14.5000',
                'overhead_cost' => '3.0400',
                'selling_price' => null,
                'description' => 'Boxy chore jacket in natural cotton canvas. Still in development: not priced yet.',
                'lines' => [
                    ['sku' => 'MAT-CAN-010', 'quantity' => '2.1000', 'waste_percent' => '10.00', 'notes' => 'Body, sleeves, pockets'],
                    ['sku' => 'MAT-BTN-017', 'quantity' => '5', 'waste_percent' => '0', 'notes' => 'Front'],
                    ['sku' => 'MAT-THR-PES', 'quantity' => '130', 'waste_percent' => '5.00', 'notes' => 'All seams'],
                    ['sku' => 'MAT-LBL-WOV', 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Neck'],
                ],
            ],
            [
                'id' => 7,
                'sku' => 'PRD-BLT-007',
                'name' => 'Leather Belt',
                'category' => ProductCategory::Accessory,
                'status' => ProductStatus::Active,
                'labor_cost' => '6.0000',
                'overhead_cost' => '1.3400',
                'selling_price' => '45.0000',
                'description' => 'Classic 35mm belt cut from cognac nappa leather with a riveted keeper.',
                'lines' => [
                    ['sku' => 'MAT-LTH-NAP', 'quantity' => '1.2000', 'waste_percent' => '15.00', 'notes' => 'Strap'],
                    ['sku' => 'MAT-RVT-009', 'quantity' => '4', 'waste_percent' => '0', 'notes' => 'Buckle and keeper'],
                ],
            ],
        ];
    }

    /**
     * The 60 sales, ids 1 to 60 in this order. The unit price of a sale is the selling price of its product.
     *
     * @return list<FixtureSale>
     */
    public static function sales(): array
    {
        $rows = [
            // [id, days ago, product id, quantity, note]
            [1, 150, 2, 30, 'Wholesale order'], [2, 138, 5, 12, 'Wholesale order'], [3, 121, 1, 6, 'Showroom'],
            [4, 110, 7, 10, 'Boutique Lyon'], [5, 96, 4, 3, 'Showroom'], [6, 84, 2, 25, 'Web shop'],
            [7, 73, 3, 6, 'Web shop'], [8, 61, 5, 9, 'Boutique Lyon'], [9, 52, 1, 7, 'Wholesale order'],
            [10, 44, 2, 18, 'Web shop'], [11, 38, 7, 5, null], [12, 33, 4, 2, 'Showroom'],
            [13, 29, 2, 10, 'Web shop'], [14, 29, 7, 4, 'Boutique Lyon'], [15, 28, 5, 5, 'Wholesale order'],
            [16, 27, 1, 3, 'Web shop'], [17, 27, 4, 1, 'Showroom'], [18, 26, 5, 5, 'Web shop'],
            [19, 26, 2, 8, null], [20, 25, 7, 5, 'Web shop'], [21, 24, 5, 7, 'Boutique Lyon'],
            [22, 24, 3, 3, 'Web shop'], [23, 23, 2, 12, 'Wholesale order'], [24, 22, 1, 6, 'Showroom'],
            [25, 21, 7, 3, 'Boutique Lyon'], [26, 21, 4, 3, 'Web shop'], [27, 20, 2, 14, 'Web shop'],
            [28, 20, 5, 6, 'Web shop'], [29, 19, 7, 6, 'Wholesale order'], [30, 18, 1, 4, 'Web shop'],
            [31, 18, 2, 6, 'Showroom'], [32, 17, 5, 8, 'Wholesale order'], [33, 17, 3, 1, null],
            [34, 16, 4, 2, 'Boutique Lyon'], [35, 16, 7, 2, 'Web shop'], [36, 15, 2, 10, 'Web shop'],
            [37, 15, 5, 4, 'Showroom'], [38, 14, 1, 5, 'Wholesale order'], [39, 14, 7, 4, 'Web shop'],
            [40, 13, 1, 5, 'Web shop'], [41, 13, 2, 15, 'Wholesale order'], [42, 13, 4, 2, 'Web shop'],
            [43, 12, 1, 6, 'Wholesale order'], [44, 11, 5, 10, 'Boutique Lyon'], [45, 11, 2, 10, 'Web shop'],
            [46, 10, 7, 4, 'Showroom'], [47, 10, 3, 3, 'Web shop'], [48, 9, 2, 10, 'Showroom'],
            [49, 9, 4, 2, 'Web shop'], [50, 9, 5, 5, 'Web shop'], [51, 8, 2, 15, 'Web shop'],
            [52, 7, 1, 5, 'Showroom'], [53, 6, 5, 8, 'Wholesale order'], [54, 5, 3, 2, 'Web shop'],
            [55, 4, 2, 12, 'Wholesale order'], [56, 3, 7, 3, 'Boutique Lyon'], [57, 3, 1, 4, 'Web shop'],
            [58, 2, 4, 2, 'Showroom'], [59, 1, 2, 20, 'Web shop'], [60, 1, 5, 6, 'Boutique Lyon'],
        ];

        return array_values(array_map(
            static fn (array $row): array => [
                'id' => $row[0],
                'days_ago' => $row[1],
                'product_id' => $row[2],
                'quantity' => $row[3],
                'note' => $row[4],
            ],
            $rows,
        ));
    }

    /**
     * Stock history keyed by material SKU, in the order that gives the entries their ids (1, 2, 3 … across the whole
     * fixture): within a day, a higher id is newer. Quantities and prices are inputs, exactly as the plan states them.
     *
     * @return array<string, list<FixtureMovement>>
     */
    public static function history(): array
    {
        $created = StockMovementType::Created;
        $restock = StockMovementType::Restock;
        $correction = StockMovementType::Correction;
        $priceChange = StockMovementType::PriceChange;

        return [
            'MAT-RVT-009' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '3000', 'unit_price' => '0.0800'],
                ['days_ago' => 20, 'type' => $restock, 'quantity' => '2000', 'quantity_after' => '5000', 'unit_price' => '0.0800', 'reference' => 'PO-2270'],
            ],
            'MAT-CAN-010' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '180', 'unit_price' => '5.4000'],
                ['days_ago' => 16, 'type' => $restock, 'quantity' => '120', 'quantity_after' => '300', 'unit_price' => '5.4000'],
            ],
            'MAT-JRS-180' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '120', 'unit_price' => '4.2000'],
                ['days_ago' => 13, 'type' => $correction, 'quantity_before' => '120', 'quantity_after' => '64', 'note' => 'Stock count'],
            ],
            'MAT-DEN-012' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '150', 'unit_price' => '8.5000'],
                ['days_ago' => 18, 'type' => $restock, 'quantity' => '100', 'quantity_after' => '250', 'unit_price' => '8.5000', 'reference' => 'PO-2281'],
            ],
            'MAT-BTN-017' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '600', 'unit_price' => '0.3200'],
                ['days_ago' => 24, 'type' => $correction, 'quantity_before' => '600', 'quantity_after' => '400', 'note' => 'Stock count'],
                ['days_ago' => 6, 'type' => $restock, 'quantity' => '500', 'quantity_after' => '900', 'unit_price' => '0.3500', 'reference' => 'PO-2291'],
                ['days_ago' => 6, 'type' => $priceChange, 'unit_cost_before' => '0.3200', 'unit_cost_after' => '0.3500'],
            ],
            'MAT-ZIP-018' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '750', 'unit_price' => '0.9500'],
                ['days_ago' => 22, 'type' => $restock, 'quantity' => '500', 'quantity_after' => '1250', 'unit_price' => '0.9500'],
            ],
            'MAT-LTH-NAP' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '340', 'unit_price' => '6.8000'],
                ['days_ago' => 10, 'type' => $restock, 'quantity' => '200', 'quantity_after' => '540', 'unit_price' => '6.8000', 'reference' => 'PO-2286'],
            ],
            'MAT-THR-PES' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '10000', 'unit_price' => '0.0040'],
                ['days_ago' => 28, 'type' => $restock, 'quantity' => '10000', 'quantity_after' => '20000', 'unit_price' => '0.0040'],
            ],
            'MAT-SOL-042' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '60', 'unit_price' => '5.9000'],
                ['days_ago' => 8, 'type' => $correction, 'quantity_before' => '60', 'quantity_after' => '0', 'note' => 'Used in production'],
            ],
            'MAT-LIN-VIS' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '180', 'unit_price' => '3.1000'],
            ],
            'MAT-LAC-120' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '400', 'unit_price' => '0.4500'],
                ['days_ago' => 26, 'type' => $restock, 'quantity' => '200', 'quantity_after' => '600', 'unit_price' => '0.4500'],
            ],
            'MAT-LBL-WOV' => [
                ['days_ago' => 37, 'type' => $created, 'quantity' => '250', 'unit_price' => '0.1000'],
                ['days_ago' => 14, 'type' => $restock, 'quantity' => '150', 'quantity_after' => '400', 'unit_price' => '0.1200'],
                ['days_ago' => 14, 'type' => $priceChange, 'unit_cost_before' => '0.1000', 'unit_cost_after' => '0.1200'],
            ],
        ];
    }

    /**
     * @return FixtureProduct|null
     */
    public static function product(int $id): ?array
    {
        foreach (self::products() as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return FixtureSale|null
     */
    public static function sale(int $id): ?array
    {
        foreach (self::sales() as $sale) {
            if ($sale['id'] === $id) {
                return $sale;
            }
        }

        return null;
    }

    /**
     * Every material SKU a fixture product references: the one query that feeds the preview costing.
     *
     * @return list<string>
     */
    public static function materialSkus(): array
    {
        $skus = [];

        foreach (self::products() as $product) {
            foreach ($product['lines'] as $line) {
                $skus[$line['sku']] = true;
            }
        }

        return array_keys($skus);
    }

    /** Fixture products whose composition uses this material SKU. */
    public static function usedInCount(string $sku): int
    {
        $count = 0;

        foreach (self::products() as $product) {
            if (array_filter($product['lines'], static fn (array $line): bool => $line['sku'] === $sku) !== []) {
                $count++;
            }
        }

        return $count;
    }

    /** URL of the static swatch of a SKU, or null when it has no photo. */
    public static function photoUrl(string $sku): ?string
    {
        return isset(self::PHOTOS[$sku]) ? asset(self::PHOTO_DIRECTORY.'/'.strtolower($sku).'.svg') : null;
    }
}
