<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Database\Factories\Concerns\DefaultWorkshop;

/**
 * The Denim Jacket of the INV-16 card (the acceptance criteria's preamble), built through the factories: colours Indigo and Black (in that
 * order), sizes S, M, L and XL, labor 12.0000, overhead 3.5000, selling price 89.0000. Button, thread and label are "Same for every
 * variant"; the two denim lines are "By colour and size" (8.5000 per m, 10 % waste), Indigo's cells under Indigo only and Black's under
 * Black only, each 1.6 · 1.8 · 2.0 · 2.2 for S · M · L · XL:
 *
 * ```
 * stock (m): Indigo 250, Black 40.   Can produce: Indigo S 112 · M 112 · L 112 · XL 103, Black S 22 · M 20 · L 18 · XL 16.
 * guaranteed 16 (Black XL, limited by Denim 12oz Black), best 112 (Indigo S, limited by the button); unit cost 33.8840 to 39.4940.
 * ```
 *
 * The workshop's list puts Black before Indigo; this moves Indigo just before Black, in that workshop, so the product's colours read
 * Indigo, Black as in the card. Light wash is in `$colours` but not on the product: attach it to see a colour without cells.
 *
 * Each call makes its own five materials and its own product (their SKUs carry a running number), so several can live in one workshop.
 */
final class VariantJacket
{
    private static int $made = 0;

    /**
     * @param  array{indigo_denim: Material, black_denim: Material, button: Material, thread: Material, label: Material}  $materials
     * @param  array{Indigo: int, Black: int, 'Light wash': int}  $colours  colour ids by name
     * @param  array{S: int, M: int, L: int, XL: int}  $sizes  size ids by label
     */
    private function __construct(
        public readonly Workshop $workshop,
        public readonly Product $product,
        public readonly array $materials,
        public readonly array $colours,
        public readonly array $sizes,
    ) {}

    /** @param Workshop|null $workshop The workshop to build in; the current one (else the first, else a new one) when null. */
    public static function create(?Workshop $workshop = null): self
    {
        $workshop ??= Workshop::query()->findOrFail(DefaultWorkshop::id());

        return CurrentWorkshop::run($workshop, fn (): self => self::build($workshop));
    }

    /** The product read back with the composition loaded, as the readers of costing get it. */
    public function fresh(): Product
    {
        return Product::query()->with('components.material')->findOrFail($this->product->id);
    }

    private static function build(Workshop $workshop): self
    {
        $number = ++self::$made;

        $colours = Colour::query()->whereIn('name', ['Indigo', 'Black', 'Light wash'])->pluck('id', 'name')->all();
        Colour::query()->where('id', $colours['Indigo'])->update(['position' => Colour::query()->where('id', $colours['Black'])->value('position') - 5]);

        $sizes = Size::query()->whereIn('label', ['S', 'M', 'L', 'XL'])->pluck('id', 'label')->all();

        $product = Product::factory()->withSizes(['S', 'M', 'L', 'XL'])->create([
            'workshop_id' => $workshop->id,
            'sku' => sprintf('PRD-VJ-%03d', $number),
            'name' => 'Denim Jacket',
            'category' => 'apparel',
            'labor_cost' => '12.0000',
            'overhead_cost' => '3.5000',
            'selling_price' => '89.0000',
        ]);
        $product->colours()->syncWithPivotValues([$colours['Indigo'], $colours['Black']], ['workshop_id' => $workshop->id]);

        $material = fn (string $key, string $name, string $category, string $unit, string $cost, string $stock): Material => Material::factory()->create([
            'workshop_id' => $workshop->id, 'sku' => sprintf('MAT-VJ%d-%s', $number, $key), 'name' => $name, 'category' => $category,
            'unit' => $unit, 'unit_cost' => $cost, 'quantity_on_hand' => $stock,
        ]);
        $materials = [
            'indigo_denim' => $material('IND', 'Denim 12oz Indigo', 'fabric', 'm', '8.5000', '250.0000'),
            'black_denim' => $material('BLK', 'Denim 12oz Black', 'fabric', 'm', '8.5000', '40.0000'),
            'button' => $material('BTN', 'Metal shank button 17mm', 'fastener', 'pc', '0.3500', '900.0000'),
            'thread' => $material('THR', 'Polyester thread', 'thread', 'm', '0.0040', '20000.0000'),
            'label' => $material('LBL', 'Woven brand label', 'label', 'pc', '0.1200', '400.0000'),
        ];

        $line = fn (Material $used, int $position, string $waste, ?string $quantity): ProductComponent => ($quantity === null
            ? ProductComponent::factory()->byVariant()
            : ProductComponent::factory())->create([
                'product_id' => $product->id, 'material_id' => $used->id, 'waste_percent' => $waste, 'position' => $position, 'notes' => null,
                ...($quantity === null ? [] : ['quantity' => $quantity]),
            ]);

        foreach ([['indigo_denim', 'Indigo', 0], ['black_denim', 'Black', 1]] as [$key, $colour, $position]) {
            $component = $line($materials[$key], $position, '10.00', null);

            foreach (['S' => '1.6000', 'M' => '1.8000', 'L' => '2.0000', 'XL' => '2.2000'] as $label => $quantity) {
                ProductComponentQuantity::factory()->create([
                    'product_component_id' => $component->id, 'colour_id' => $colours[$colour], 'size_id' => $sizes[$label], 'quantity' => $quantity,
                ]);
            }
        }

        $line($materials['button'], 2, '0.00', '8.0000');
        $line($materials['thread'], 3, '5.00', '120.0000');
        $line($materials['label'], 4, '0.00', '1.0000');

        return new self($workshop, $product, $materials, $colours, $sizes);
    }
}
