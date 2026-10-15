<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Products\SyncProductComponents;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\Concerns\ResolvesDemoWorkshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 7 demo products of the approved mockup (docs/plans/phase-2-ui-first-preview.md, "Products") with their compositions, as real
 * rows on the demo materials of {@see DemoCatalogSeeder}. With those materials they give the worked example of the costing domain
 * skill (Denim Jacket: unit cost 35.7540, 112 units, limited by the button).
 *
 * Idempotent and never destructive: a product is created only when no product of the demo workshop has its SKU, so running it again
 * creates nothing, a product the user edited is left alone, and one the user deleted stays deleted (F-37: a soft-deleted
 * demo product is not recreated). A composition line whose material is missing (deleted, or never
 * seeded) is skipped; the product is still created, with the lines that exist.
 */
class DemoProductsSeeder extends Seeder
{
    use ResolvesDemoWorkshop;

    /**
     * Line quantities are in the material's own unit; materials are referenced by SKU.
     *
     * @var list<array{
     *     sku: string,
     *     name: string,
     *     category: string,
     *     status: string,
     *     labor_cost: string,
     *     overhead_cost: string,
     *     selling_price: string|null,
     *     description: string,
     *     lines: list<array{sku: string, quantity: string, waste_percent: string, notes: string}>,
     * }>
     */
    public const PRODUCTS = [
        [
            'sku' => 'PRD-JKT-001',
            'name' => 'Denim Jacket',
            'category' => 'apparel',
            'status' => 'active',
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
            'sku' => 'PRD-TSH-002',
            'name' => 'Classic White T-shirt',
            'category' => 'apparel',
            'status' => 'active',
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
            'sku' => 'PRD-SNK-003',
            'name' => 'Canvas Low Sneaker',
            'category' => 'footwear',
            'status' => 'active',
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
            'sku' => 'PRD-TOT-004',
            'name' => 'Leather Tote',
            'category' => 'bag',
            'status' => 'active',
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
            'sku' => 'PRD-JNS-005',
            'name' => 'Denim Jeans 5-pocket',
            'category' => 'apparel',
            'status' => 'active',
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
            'sku' => 'PRD-JKT-006',
            'name' => 'Canvas Chore Jacket',
            'category' => 'apparel',
            'status' => 'draft',
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
            'sku' => 'PRD-BLT-007',
            'name' => 'Leather Belt',
            'category' => 'accessory',
            'status' => 'active',
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

    public function run(SyncProductComponents $syncComponents): void
    {
        $workshopId = $this->demoWorkshopId();
        $existing = Product::query()->withTrashed()->where('workshop_id', $workshopId)->whereIn('sku', array_column(self::PRODUCTS, 'sku'))->pluck('sku')->all();
        $missing = array_filter(self::PRODUCTS, static fn (array $product): bool => ! in_array($product['sku'], $existing, true));

        if ($missing === []) {
            return;
        }

        $categories = ProductCategory::query()->pluck('id', 'code');
        $materials = Material::query()->without('category', 'unit')->where('workshop_id', $workshopId)->pluck('id', 'sku');

        foreach ($missing as $demo) {
            // A default category the user has since deleted (it was unused) can't be resolved: that product is skipped.
            if (! isset($categories[$demo['category']])) {
                continue;
            }

            DB::transaction(function () use ($demo, $categories, $materials, $syncComponents, $workshopId): void {
                $product = new Product([
                    ...array_diff_key($demo, ['category' => true, 'lines' => true]),
                    'category_id' => $categories[$demo['category']],
                ]);
                // Never mass-assignable.
                $product->workshop_id = $workshopId;
                $product->save();

                $lines = [];

                foreach ($demo['lines'] as $line) {
                    if (isset($materials[$line['sku']])) {
                        $lines[] = [
                            'material_id' => $materials[$line['sku']],
                            'quantity' => $line['quantity'],
                            'waste_percent' => $line['waste_percent'],
                            'notes' => $line['notes'],
                        ];
                    }
                }

                $syncComponents->handle($product, $lines);
            });
        }
    }
}
