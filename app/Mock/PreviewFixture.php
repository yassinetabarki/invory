<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Enums\StockMovementType;

/**
 * The raw INPUTS of the UI-first preview: everything that has no table yet (sales, stock history, photo swatches). Source:
 * docs/plans/phase-2-ui-first-preview.md, "Preview fixture".
 *
 * Nothing here is derived: totals and rankings are computed on the server by the production domain class (SalesCalculator).
 * Dates are `days_ago`, relative to today. The materials and the demo products are real rows seeded by DemoCatalogSeeder and
 * DemoProductsSeeder; the sales and the history reference them by SKU, and a SKU with no row is skipped.
 *
 * @phpstan-type FixtureSale array{id: int, days_ago: int, product_sku: string, quantity: int, unit_price: string, note: string|null}
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
     * The 60 sales, ids 1 to 60 in this order. The unit price of a sale is the price its product was sold at (the demo selling
     * price): an input of the fixture, so editing a product's selling price doesn't rewrite the history.
     *
     * @return list<FixtureSale>
     */
    public static function sales(): array
    {
        $jacket = 'PRD-JKT-001';
        $tshirt = 'PRD-TSH-002';
        $sneaker = 'PRD-SNK-003';
        $tote = 'PRD-TOT-004';
        $jeans = 'PRD-JNS-005';
        $belt = 'PRD-BLT-007';
        $prices = [$jacket => '89.0000', $tshirt => '24.0000', $sneaker => '69.0000', $tote => '249.0000', $jeans => '79.0000', $belt => '45.0000'];
        $bySku = [1 => $jacket, 2 => $tshirt, 3 => $sneaker, 4 => $tote, 5 => $jeans, 7 => $belt];

        $rows = [
            // [id, days ago, product (1 jacket, 2 t-shirt, 3 sneaker, 4 tote, 5 jeans, 7 belt), quantity, note]
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
                'product_sku' => $bySku[$row[2]],
                'quantity' => $row[3],
                'unit_price' => $prices[$bySku[$row[2]]],
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

    /** URL of the static swatch of a SKU, or null when it has no photo. */
    public static function photoUrl(string $sku): ?string
    {
        return isset(self::PHOTOS[$sku]) ? asset(self::PHOTO_DIRECTORY.'/'.strtolower($sku).'.svg') : null;
    }
}
