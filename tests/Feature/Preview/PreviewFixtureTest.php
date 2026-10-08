<?php

use App\Mock\PreviewFixture;
use App\Models\Material;
use App\Models\User;
use App\Services\Inventory\InventorySummary;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| The seeded materials and the static swatches (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| DemoCatalogSeeder seeds the mockup's 12 materials for real, idempotently by SKU. The expected rows below are the plan's
| "Materials" table, typed out again so that a change to the seeder is caught here.
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

        $material = previewMaterial($sku);

        expect($material->name)->toBe($name)
            ->and($material->category->value)->toBe($category)
            ->and($material->unit->value)->toBe($unit)
            ->and($material->unit_cost)->toBe($cost)
            ->and($material->quantity_on_hand)->toBe($stock)
            ->and($material->reorder_level)->toBe($reorder)
            ->and($material->supplier_name)->not->toBeNull()
            ->and($material->supplier_reference)->not->toBeNull();
    })->with('plannedMaterials');

    it('seeds the supplier details of the button as the plan states them', function () {
        $this->seed(DemoCatalogSeeder::class);

        $button = previewMaterial('MAT-BTN-017');

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
        previewMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7', 'unit_cost' => '9']);
        $other = Material::factory()->create(['sku' => 'OTHER-1', 'name' => 'Mine', 'quantity_on_hand' => '3']);

        $this->seed(DemoCatalogSeeder::class);

        expect(previewMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('900.0000')
            ->and(previewMaterial('MAT-BTN-017')->unit_cost)->toBe('0.3500')
            ->and($other->fresh()->name)->toBe('Mine')
            ->and($other->fresh()->quantity_on_hand)->toBe('3.0000')
            ->and(Material::query()->count())->toBe(13);
    });

    it('is part of the database seeder, next to the test user', function () {
        $this->seed(DatabaseSeeder::class);

        expect(Material::query()->count())->toBe(12)
            ->and(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
    });
});

describe('static swatches', function () {
    it('has a swatch for every photo of the fixture and no other file', function () {
        $expected = array_map(fn (string $sku): string => strtolower($sku).'.svg', array_keys(PreviewFixture::PHOTOS));
        $files = array_map('basename', glob(public_path(PreviewFixture::PHOTO_DIRECTORY.'/*')));

        expect($expected)->toHaveCount(16)
            ->and($files)->toEqualCanonicalizing($expected);
    });

    it('draws each swatch in the tint and ink of the plan', function (string $sku, array $colours) {
        $svg = file_get_contents(public_path(PreviewFixture::PHOTO_DIRECTORY.'/'.strtolower($sku).'.svg'));
        $xml = simplexml_load_string($svg);

        expect($xml)->not->toBeFalse()
            ->and(strtoupper((string) $xml->rect[0]['fill']))->toBe(strtoupper($colours['tint']))
            ->and(strtoupper((string) $xml->text['fill']))->toBe(strtoupper($colours['ink']));
    })->with(fn () => collect(PreviewFixture::PHOTOS)->map(fn (array $colours, string $sku): array => [$sku, $colours])->all());

    it('links a photo to exactly the SKUs that have one', function () {
        // The plan: the lining, the laces and the Chore Jacket have no photo.
        expect(PreviewFixture::photoUrl('MAT-LIN-VIS'))->toBeNull()
            ->and(PreviewFixture::photoUrl('MAT-LAC-120'))->toBeNull()
            ->and(PreviewFixture::photoUrl('PRD-JKT-006'))->toBeNull()
            ->and(PreviewFixture::photoUrl('MAT-DEN-012'))->toBe(asset('mock/photos/mat-den-012.svg'))
            ->and(PreviewFixture::photoUrl('PRD-JKT-001'))->toBe(asset('mock/photos/prd-jkt-001.svg'));
    });
});

describe('fixture inputs', function () {
    it('has the 7 products and 60 sales of the plan, ids from 1', function () {
        expect(array_column(PreviewFixture::products(), 'id'))->toBe([1, 2, 3, 4, 5, 6, 7])
            ->and(array_column(PreviewFixture::sales(), 'id'))->toBe(range(1, 60))
            ->and(PreviewFixture::product(8))->toBeNull()
            ->and(PreviewFixture::sale(61))->toBeNull();
    });

    it('only sells products that have a selling price', function () {
        $priced = array_filter(PreviewFixture::products(), fn (array $product): bool => $product['selling_price'] !== null);

        foreach (PreviewFixture::sales() as $sale) {
            expect(array_column($priced, 'id'))->toContain($sale['product_id']);
        }
    });

    it('references only the seeded materials', function () {
        $seeded = array_column(DemoCatalogSeeder::MATERIALS, 'sku');

        expect(PreviewFixture::materialSkus())->each->toBeIn($seeded)
            ->and(array_keys(PreviewFixture::history()))->each->toBeIn($seeded);
    });
});

it('does not expose any preview ability in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    expect(Gate::forUser(User::factory()->create())->allows('access-preview'))->toBeFalse();
});
