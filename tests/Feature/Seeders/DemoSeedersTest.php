<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use App\Models\User;
use App\Services\Costing\ProductCostingService;
use App\Services\Inventory\InventorySummary;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoProductsSeeder;
use Database\Seeders\DemoSalesSeeder;
use Tests\Support\DemoHistory;

/*
|--------------------------------------------------------------------------
| The demo seeders: the seeded materials and products and the demo sales prices (S-13c, S-04b)
|--------------------------------------------------------------------------
|
| DemoCatalogSeeder seeds the mockup's 12 materials for real, idempotently by SKU, then DemoProductsSeeder the 7 demo products
| (only the ones whose SKU is missing). The expected rows below are the plan's "Materials" and "Products" tables, typed out again
| so that a change to a seeder is caught here.
*/

/** [sku, name, category, unit, unit cost, stock, reorder at]. */
dataset('plannedMaterials', [
    'Brass rivet' => ['MAT-RVT-009', 'Brass rivet 9mm', 'hardware', 'pc', '0.0800', '5000.0000', '1000.0000'],
    'Cotton canvas' => ['MAT-CAN-010', 'Cotton canvas 10oz Natural', 'fabric', 'm', '5.4000', '300.0000', '60.0000'],
    'Cotton jersey' => ['MAT-JRS-180', 'Cotton jersey 180gsm White', 'fabric', 'm', '4.2000', '64.0000', '80.0000'],
    'Denim' => ['MAT-DEN-012', 'Denim 12oz Indigo', 'fabric', 'm', '8.5000', '250.0000', '100.0000'],
    'Metal shank button' => ['MAT-BTN-017', 'Metal shank button 17mm', 'fastener', 'pc', '0.3500', '900.0000', '1000.0000'],
    'Metal zipper' => ['MAT-ZIP-018', 'Metal zipper 18cm', 'fastener', 'pc', '0.9500', '1250.0000', '300.0000'],
    'Nappa leather' => ['MAT-LTH-NAP', 'Nappa leather Cognac', 'leather', 'sqft', '6.8000', '540.0000', '150.0000'],
    'Polyester thread' => ['MAT-THR-PES', 'Polyester thread', 'thread', 'm', '0.0040', '20000.0000', '5000.0000'],
    'Rubber cupsole' => ['MAT-SOL-042', 'Rubber cupsole 42', 'sole', 'pair', '5.9000', '0.0000', '40.0000'],
    'Viscose lining' => ['MAT-LIN-VIS', 'Viscose lining Black', 'lining', 'm', '3.1000', '180.0000', '50.0000'],
    'Waxed cotton laces' => ['MAT-LAC-120', 'Waxed cotton laces 120cm', 'trim', 'pair', '0.4500', '600.0000', '200.0000'],
    'Woven brand label' => ['MAT-LBL-WOV', 'Woven brand label', 'label', 'pc', '0.1200', '400.0000', '500.0000'],
]);

describe('DemoCatalogSeeder', function () {
    it('seeds the plan\'s materials with their exact values', function (string $sku, string $name, string $category, string $unit, string $cost, string $stock, string $reorder) {
        $this->seed(DemoCatalogSeeder::class);

        $material = demoMaterial($sku);

        expect($material->name)->toBe($name)
            ->and($material->category->code)->toBe($category)
            ->and($material->unit->code)->toBe($unit)
            ->and($material->unit_cost)->toBe($cost)
            ->and($material->quantity_on_hand)->toBe($stock)
            ->and($material->reorder_level)->toBe($reorder)
            ->and($material->supplier_name)->not->toBeNull()
            ->and($material->supplier_reference)->not->toBeNull();
    })->with('plannedMaterials');

    it('seeds the supplier details of the button as the plan states them', function () {
        $this->seed(DemoCatalogSeeder::class);

        $button = demoMaterial('MAT-BTN-017');

        expect($button->supplier_name)->toBe('Northgate Trims')
            ->and($button->supplier_reference)->toBe('NT-17-SHK-AB')
            ->and($button->color)->toBe('Antique brass')
            ->and($button->composition)->toBe('Zinc alloy')
            ->and($button->notes)->toBe('Hammer-on shank. Needs the tack-button press.');
    });

    it('seeds 12 materials worth 10544.3, with 3 low and 1 out of stock', function () {
        $this->seed(DemoCatalogSeeder::class);

        $materials = Material::query()->get();
        $statuses = $materials->countBy(fn (Material $material): string => $material->stockStatus()->value);

        expect($materials)->toHaveCount(12)
            ->and(app(InventorySummary::class)->summarize())->toBe([
                'materials_count' => 12,
                'inventory_value' => '10544.3000',
                'needs_restock_count' => 4,
                'low_stock_count' => 3,
                'out_of_stock_count' => 1,
            ])
            ->and($statuses->all())->toEqual(['in_stock' => 8, 'low' => 3, 'out' => 1]);
    });

    it('is idempotent: running it again creates nothing twice', function () {
        $this->seed(DemoCatalogSeeder::class);
        $ids = Material::query()->orderBy('id')->pluck('id', 'sku')->all();

        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoCatalogSeeder::class);

        expect(Material::withTrashed()->count())->toBe(12)
            ->and(Material::query()->orderBy('id')->pluck('id', 'sku')->all())->toBe($ids);
    });

    it('puts the 12 materials back to their values and leaves other materials alone', function () {
        $this->seed(DemoCatalogSeeder::class);
        demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7', 'unit_cost' => '9']);
        $other = Material::factory()->create(['sku' => 'OTHER-1', 'name' => 'Mine', 'quantity_on_hand' => '3']);

        $this->seed(DemoCatalogSeeder::class);

        expect(demoMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('900.0000')
            ->and(demoMaterial('MAT-BTN-017')->unit_cost)->toBe('0.3500')
            ->and($other->fresh()->name)->toBe('Mine')
            ->and($other->fresh()->quantity_on_hand)->toBe('3.0000')
            ->and(Material::query()->count())->toBe(13);
    });

    it('is part of the database seeder, next to the test user', function () {
        $this->seed(DatabaseSeeder::class);

        expect(Material::query()->count())->toBe(12)
            ->and(Product::query()->count())->toBe(7)
            ->and(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
    });
});

/** [sku, name, category, status, labor, overhead, selling price, [material SKUs of the lines, in order]]. */
dataset('plannedProducts', [
    'Denim Jacket' => ['PRD-JKT-001', 'Denim Jacket', 'apparel', 'active', '12.0000', '3.5000', '89.0000', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Classic White T-shirt' => ['PRD-TSH-002', 'Classic White T-shirt', 'apparel', 'active', '3.2000', '0.7500', '24.0000', ['MAT-JRS-180', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Canvas Low Sneaker' => ['PRD-SNK-003', 'Canvas Low Sneaker', 'footwear', 'active', '9.3000', '2.0000', '69.0000', ['MAT-CAN-010', 'MAT-SOL-042', 'MAT-LAC-120', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Leather Tote' => ['PRD-TOT-004', 'Leather Tote', 'bag', 'active', '22.5000', '4.7700', '249.0000', ['MAT-LTH-NAP', 'MAT-LIN-VIS', 'MAT-ZIP-018', 'MAT-RVT-009', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Denim Jeans 5-pocket' => ['PRD-JNS-005', 'Denim Jeans 5-pocket', 'apparel', 'active', '11.0000', '2.9700', '79.0000', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-RVT-009', 'MAT-ZIP-018', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Canvas Chore Jacket' => ['PRD-JKT-006', 'Canvas Chore Jacket', 'apparel', 'draft', '14.5000', '3.0400', null, ['MAT-CAN-010', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV']],
    'Leather Belt' => ['PRD-BLT-007', 'Leather Belt', 'accessory', 'active', '6.0000', '1.3400', '45.0000', ['MAT-LTH-NAP', 'MAT-RVT-009']],
]);

describe('DemoProductsSeeder', function () {
    it('seeds the plan\'s products with their compositions', function (string $sku, string $name, string $category, string $status, string $labor, string $overhead, ?string $price, array $materials) {
        $this->seed(DemoCatalogSeeder::class);

        $product = demoProduct($sku);
        $lines = ProductComponent::query()->where('product_id', $product->id)->orderBy('position')->get();

        expect($product->name)->toBe($name)
            ->and($product->category->code)->toBe($category)
            ->and($product->status->value)->toBe($status)
            ->and($product->labor_cost)->toBe($labor)
            ->and($product->overhead_cost)->toBe($overhead)
            ->and($product->selling_price)->toBe($price)
            ->and($product->description)->not->toBeEmpty()
            ->and($product->created_by)->toBeNull()
            ->and($lines->map(fn (ProductComponent $line): string => Material::query()->without('category', 'unit')->findOrFail($line->material_id)->sku)->all())->toBe($materials)
            ->and($lines->pluck('position')->all())->toBe(range(0, count($materials) - 1));
    })->with('plannedProducts');

    it('seeds the lines of the Denim Jacket with the worked example of the costing skill', function () {
        $this->seed(DemoCatalogSeeder::class);

        $jacket = Product::query()->with('components.material')->where('sku', 'PRD-JKT-001')->firstOrFail();
        $costing = app(ProductCostingService::class)->cost($jacket);

        expect($jacket->components->pluck('quantity')->all())->toBe(['1.8000', '8.0000', '120.0000', '1.0000'])
            ->and($jacket->components->pluck('waste_percent')->all())->toBe(['10.00', '0.00', '5.00', '0.00'])
            ->and($costing->result->cost->unitCost)->toBe('35.7540')
            ->and($costing->result->capacity->maxProducible)->toBe(112)
            ->and($costing->result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm')
            ->and($costing->margin?->percent)->toBe('59.83');
    });

    it('is idempotent: running it twice creates nothing new', function () {
        $this->seed(DemoCatalogSeeder::class);
        $products = Product::query()->orderBy('id')->pluck('id', 'sku')->all();
        $lines = ProductComponent::query()->orderBy('id')->pluck('id')->all();

        $this->seed(DemoProductsSeeder::class);
        $this->seed(DemoCatalogSeeder::class);

        expect(Product::withTrashed()->count())->toBe(7)
            ->and(Product::query()->orderBy('id')->pluck('id', 'sku')->all())->toBe($products)
            ->and(ProductComponent::query()->orderBy('id')->pluck('id')->all())->toBe($lines);
    });

    it('never overwrites a product the user edited, nor its composition', function () {
        $this->seed(DemoCatalogSeeder::class);
        $jacket = demoProduct('PRD-JKT-001');
        $jacket->update(['name' => 'My jacket', 'selling_price' => '120', 'labor_cost' => '20']);
        ProductComponent::query()->where('product_id', $jacket->id)->where('material_id', demoMaterial('MAT-LBL-WOV')->id)->delete();
        ProductComponent::query()->where('product_id', $jacket->id)->where('material_id', demoMaterial('MAT-BTN-017')->id)->update(['quantity' => '3']);

        $this->seed(DemoProductsSeeder::class);

        $jacket->refresh();
        $lines = ProductComponent::query()->where('product_id', $jacket->id)->orderBy('position')->pluck('quantity')->all();

        expect($jacket->name)->toBe('My jacket')
            ->and($jacket->selling_price)->toBe('120.0000')
            ->and($jacket->labor_cost)->toBe('20.0000')
            ->and($lines)->toBe(['1.8000', '3.0000', '120.0000']);
    });

    it('leaves the other products of the user alone', function () {
        $this->seed(DemoCatalogSeeder::class);
        $mine = Product::factory()->create(['sku' => 'MINE-1', 'name' => 'Mine']);

        $this->seed(DemoProductsSeeder::class);

        expect(Product::query()->count())->toBe(8)
            ->and($mine->fresh()->name)->toBe('Mine');
    });

    it('creates only the demo products whose SKU is missing, and never one that was deleted (F-37)', function () {
        $this->seed(DemoCatalogSeeder::class);
        $kept = demoProduct('PRD-TSH-002')->id;
        $belt = demoProduct('PRD-BLT-007');
        $belt->delete();
        Product::query()->where('sku', 'PRD-SNK-003')->forceDelete();

        $this->seed(DemoProductsSeeder::class);

        // The deleted Belt exists (soft-deleted), so deletions stick: it is not created again. A SKU with no row at all is.
        expect(Product::query()->count())->toBe(6)
            ->and(Product::withTrashed()->count())->toBe(7)
            ->and(Product::query()->where('sku', 'PRD-BLT-007')->exists())->toBeFalse()
            ->and(Product::withTrashed()->where('sku', 'PRD-BLT-007')->value('id'))->toBe($belt->id)
            ->and(demoProduct('PRD-TSH-002')->id)->toBe($kept)
            ->and(ProductComponent::query()->where('product_id', demoProduct('PRD-SNK-003')->id)->count())->toBe(5);
    });

    it('skips a line whose material is missing and still creates the product', function () {
        $this->seed(DemoCatalogSeeder::class);
        Product::query()->forceDelete();
        demoMaterial('MAT-LAC-120')->delete();

        $this->seed(DemoProductsSeeder::class);

        $sneaker = demoProduct('PRD-SNK-003');
        $skus = ProductComponent::query()->where('product_id', $sneaker->id)->orderBy('position')->get()
            ->map(fn (ProductComponent $line): string => Material::query()->without('category', 'unit')->findOrFail($line->material_id)->sku)->all();

        expect(Product::query()->count())->toBe(7)
            // The laces are gone; the lines that exist keep a gap-free position.
            ->and($skus)->toBe(['MAT-CAN-010', 'MAT-SOL-042', 'MAT-THR-PES', 'MAT-LBL-WOV'])
            ->and(ProductComponent::query()->where('product_id', $sneaker->id)->orderBy('position')->pluck('position')->all())->toBe([0, 1, 2, 3]);
    });

    it('creates no composition when none of the materials exists', function () {
        // Only the products seeder, on a database without the demo materials.
        $this->seed(DemoProductsSeeder::class);

        expect(Product::query()->count())->toBe(7)
            ->and(ProductComponent::query()->count())->toBe(0);
    });

    it('skips a product whose default category was deleted', function () {
        $this->seed(DemoCatalogSeeder::class);
        Product::query()->forceDelete();
        ProductCategory::query()->where('code', 'bag')->delete();

        $this->seed(DemoProductsSeeder::class);

        expect(Product::query()->count())->toBe(6)
            ->and(Product::query()->where('sku', 'PRD-TOT-004')->exists())->toBeFalse();
    });
});

describe('fixture inputs', function () {
    it('has the 7 demo products', function () {
        expect(array_column(DemoProductsSeeder::PRODUCTS, 'sku'))->toBe(array_values(DEMO_PRODUCT_SKUS));
    });

    it('sells at the selling price of the demo products', function () {
        $prices = array_column(DemoProductsSeeder::PRODUCTS, 'selling_price', 'sku');

        expect(array_keys(DemoSalesSeeder::PRICES))->each->toBeIn(array_keys($prices));

        foreach (DemoSalesSeeder::PRICES as $sku => $price) {
            expect($price)->toBe($prices[$sku]);
        }
    });

    it('references only the seeded materials', function () {
        $seeded = array_column(DemoCatalogSeeder::MATERIALS, 'sku');
        $referenced = array_unique(array_merge(...array_map(fn (array $product): array => array_column($product['lines'], 'sku'), DemoProductsSeeder::PRODUCTS)));

        expect($referenced)->each->toBeIn($seeded)
            ->and(array_keys(DemoHistory::entries()))->each->toBeIn($seeded);
    });
});
