<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialCategory;
use App\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Database\Seeder;

/**
 * The 12 materials of the approved mockup (docs/plans/phase-2-ui-first-preview.md, "Materials"), so the UI-first preview
 * has real stock to cost its fixture products against. Together with that fixture they give the inventory value
 * "10544.3000", 4 materials to restock (3 low, 1 out of stock) and the worked example of the costing domain skill.
 *
 * Idempotent: rows are keyed by SKU, so running it again resets these 12 materials to their values here and creates
 * nothing twice. Other materials are left alone.
 */
class DemoCatalogSeeder extends Seeder
{
    /**
     * @var list<array{
     *     sku: string,
     *     name: string,
     *     category: MaterialCategory,
     *     unit: UnitOfMeasure,
     *     unit_cost: string,
     *     quantity_on_hand: string,
     *     reorder_level: string,
     *     supplier_name: string,
     *     supplier_reference: string,
     *     color: string,
     *     composition: string,
     *     notes: string|null,
     * }>
     */
    public const MATERIALS = [
        [
            'sku' => 'MAT-RVT-009',
            'name' => 'Brass rivet 9mm',
            'category' => MaterialCategory::Hardware,
            'unit' => UnitOfMeasure::Piece,
            'unit_cost' => '0.0800',
            'quantity_on_hand' => '5000',
            'reorder_level' => '1000',
            'supplier_name' => 'Northgate Trims',
            'supplier_reference' => 'NT-RVT-09-BR',
            'color' => 'Brass',
            'composition' => 'Solid brass',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-CAN-010',
            'name' => 'Cotton canvas 10oz Natural',
            'category' => MaterialCategory::Fabric,
            'unit' => UnitOfMeasure::Metre,
            'unit_cost' => '5.4000',
            'quantity_on_hand' => '300',
            'reorder_level' => '60',
            'supplier_name' => 'Mill & Loom',
            'supplier_reference' => 'ML-CAN-10-NAT',
            'color' => 'Natural',
            'composition' => '100% cotton',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-JRS-180',
            'name' => 'Cotton jersey 180gsm White',
            'category' => MaterialCategory::Fabric,
            'unit' => UnitOfMeasure::Metre,
            'unit_cost' => '4.2000',
            'quantity_on_hand' => '64',
            'reorder_level' => '80',
            'supplier_name' => 'Mill & Loom',
            'supplier_reference' => 'ML-JRS-180-WH',
            'color' => 'Optic white',
            'composition' => '100% combed cotton',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-DEN-012',
            'name' => 'Denim 12oz Indigo',
            'category' => MaterialCategory::Fabric,
            'unit' => UnitOfMeasure::Metre,
            'unit_cost' => '8.5000',
            'quantity_on_hand' => '250',
            'reorder_level' => '100',
            'supplier_name' => 'Indigo Mills',
            'supplier_reference' => 'IM-DEN-12-IND',
            'color' => 'Indigo',
            'composition' => '100% cotton',
            'notes' => 'Rigid, unwashed. Allow for shrinkage.',
        ],
        [
            'sku' => 'MAT-BTN-017',
            'name' => 'Metal shank button 17mm',
            'category' => MaterialCategory::Fastener,
            'unit' => UnitOfMeasure::Piece,
            'unit_cost' => '0.3500',
            'quantity_on_hand' => '900',
            'reorder_level' => '1000',
            'supplier_name' => 'Northgate Trims',
            'supplier_reference' => 'NT-17-SHK-AB',
            'color' => 'Antique brass',
            'composition' => 'Zinc alloy',
            'notes' => 'Hammer-on shank. Needs the tack-button press.',
        ],
        [
            'sku' => 'MAT-ZIP-018',
            'name' => 'Metal zipper 18cm',
            'category' => MaterialCategory::Fastener,
            'unit' => UnitOfMeasure::Piece,
            'unit_cost' => '0.9500',
            'quantity_on_hand' => '1250',
            'reorder_level' => '300',
            'supplier_name' => 'Northgate Trims',
            'supplier_reference' => 'NT-ZIP-18-GM',
            'color' => 'Gunmetal',
            'composition' => 'Metal teeth, cotton tape',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-LTH-NAP',
            'name' => 'Nappa leather Cognac',
            'category' => MaterialCategory::Leather,
            'unit' => UnitOfMeasure::SquareFoot,
            'unit_cost' => '6.8000',
            'quantity_on_hand' => '540',
            'reorder_level' => '150',
            'supplier_name' => 'Conceria Tosca',
            'supplier_reference' => 'CT-NAP-CGN',
            'color' => 'Cognac',
            'composition' => 'Full-grain bovine leather',
            'notes' => 'Sold by the hide. Check for scars before cutting.',
        ],
        [
            'sku' => 'MAT-THR-PES',
            'name' => 'Polyester thread',
            'category' => MaterialCategory::Thread,
            'unit' => UnitOfMeasure::Metre,
            'unit_cost' => '0.0040',
            'quantity_on_hand' => '20000',
            'reorder_level' => '5000',
            'supplier_name' => 'ThreadWorks',
            'supplier_reference' => 'TW-PES-40',
            'color' => 'Natural white',
            'composition' => '100% polyester',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-SOL-042',
            'name' => 'Rubber cupsole 42',
            'category' => MaterialCategory::Sole,
            'unit' => UnitOfMeasure::Pair,
            'unit_cost' => '5.9000',
            'quantity_on_hand' => '0',
            'reorder_level' => '40',
            'supplier_name' => 'Vulcan Soles',
            'supplier_reference' => 'VS-CUP-42',
            'color' => 'Off-white',
            'composition' => 'Natural rubber',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-LIN-VIS',
            'name' => 'Viscose lining Black',
            'category' => MaterialCategory::Lining,
            'unit' => UnitOfMeasure::Metre,
            'unit_cost' => '3.1000',
            'quantity_on_hand' => '180',
            'reorder_level' => '50',
            'supplier_name' => 'Mill & Loom',
            'supplier_reference' => 'ML-VIS-BLK',
            'color' => 'Black',
            'composition' => '100% viscose',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-LAC-120',
            'name' => 'Waxed cotton laces 120cm',
            'category' => MaterialCategory::Trim,
            'unit' => UnitOfMeasure::Pair,
            'unit_cost' => '0.4500',
            'quantity_on_hand' => '600',
            'reorder_level' => '200',
            'supplier_name' => 'Northgate Trims',
            'supplier_reference' => 'NT-LAC-120-NAT',
            'color' => 'Natural',
            'composition' => 'Waxed cotton',
            'notes' => null,
        ],
        [
            'sku' => 'MAT-LBL-WOV',
            'name' => 'Woven brand label',
            'category' => MaterialCategory::Label,
            'unit' => UnitOfMeasure::Piece,
            'unit_cost' => '0.1200',
            'quantity_on_hand' => '400',
            'reorder_level' => '500',
            'supplier_name' => 'LabelCraft',
            'supplier_reference' => 'LC-WOV-01',
            'color' => 'Black',
            'composition' => 'Polyester damask',
            'notes' => null,
        ],
    ];

    public function run(): void
    {
        foreach (self::MATERIALS as $material) {
            Material::query()->updateOrCreate(
                ['sku' => $material['sku']],
                array_diff_key($material, ['sku' => true]),
            );
        }
    }
}
