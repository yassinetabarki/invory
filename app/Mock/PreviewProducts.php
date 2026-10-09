<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\Margin;
use App\Domain\Costing\MarginCalculator;
use App\Domain\Sales\SalesCalculator;
use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Models\Material;
use App\Models\ProductCategory;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * The fixture products, resolved against the real materials and costed by the production domain code, and the builders of
 * every product-shaped prop (`ProductListItem`, `Product`, `MaterialUsage`, `UnproducibleAlert`).
 *
 * The materials come from the database by SKU in ONE query per request (`load()`), so editing a material's stock moves the
 * preview capacities immediately. A line whose SKU is missing (or soft-deleted) is skipped.
 *
 * @phpstan-import-type FixtureProduct from PreviewFixture
 */
final class PreviewProducts
{
    /** @var array<int, Material> */
    private array $materialsById = [];

    /** @var list<PreviewProduct>|null */
    private ?array $products = null;

    /** @var array<string, string>|null Product category code => name, read once per request (see `categoryName()`). */
    private ?array $categoryNames = null;

    /** @param array<string, Material> $materialsBySku */
    private function __construct(private readonly array $materialsBySku)
    {
        foreach ($materialsBySku as $material) {
            $this->materialsById[$material->id] = $material;
        }
    }

    /** Runs the single materials query of the request. */
    public static function load(): self
    {
        // The costing reads the unit (always eager-loaded), never the category: skip that query.
        return self::from(Material::query()->without('category')->whereIn('sku', PreviewFixture::materialSkus())->get());
    }

    /**
     * Resolves the fixture against materials a page has already loaded (so a page that also lists every material, such as
     * the product form, reads the table once). Materials the fixture does not reference are ignored.
     *
     * @param  iterable<Material>  $materials
     */
    public static function from(iterable $materials): self
    {
        $skus = array_flip(PreviewFixture::materialSkus());
        $bySku = [];

        foreach ($materials as $material) {
            if (isset($skus[$material->sku])) {
                $bySku[$material->sku] = $material;
            }
        }

        return new self($bySku);
    }

    /**
     * Every fixture product, costed, in id order.
     *
     * @return list<PreviewProduct>
     */
    public function all(): array
    {
        return $this->products ??= array_map($this->resolve(...), PreviewFixture::products());
    }

    public function find(int $id): ?PreviewProduct
    {
        foreach ($this->all() as $product) {
            if ($product->id() === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Products by name (case-insensitive), then id.
     *
     * @param  list<PreviewProduct>  $products
     * @return list<PreviewProduct>
     */
    public static function byName(array $products): array
    {
        usort($products, static fn (PreviewProduct $a, PreviewProduct $b): int => [strtolower($a->name()), $a->id()] <=> [strtolower($b->name()), $b->id()]);

        return $products;
    }

    /**
     * The products list filters: name or SKU (case-insensitive), category, status, and "can't produce" (`max_producible = 0`).
     *
     * @param  list<PreviewProduct>  $products
     * @return list<PreviewProduct>
     */
    public static function filter(array $products, ?string $search, ?string $category, ?string $status, bool $cantProduce): array
    {
        return array_values(array_filter($products, static function (PreviewProduct $product) use ($search, $category, $status, $cantProduce): bool {
            $matchesSearch = $search === null || $search === ''
                || mb_stripos($product->name(), $search) !== false
                || mb_stripos($product->fixture['sku'], $search) !== false;

            return $matchesSearch
                && ($category === null || $product->fixture['category'] === $category)
                && ($status === null || $product->fixture['status']->value === $status)
                && (! $cantProduce || $product->maxProducible() === 0);
        }));
    }

    /** The id when it is an active product (the only ones that can be sold), else null. */
    public static function sellableId(?int $id): ?int
    {
        $product = $id === null ? null : PreviewFixture::product($id);

        return $product !== null && $product['status'] === ProductStatus::Active ? $id : null;
    }

    /** Products (any status) that can't be made now: `max_producible = 0`. Products without a composition are not counted. */
    public function unproducibleCount(): int
    {
        return count(array_filter($this->all(), static fn (PreviewProduct $product): bool => $product->maxProducible() === 0));
    }

    /**
     * `ProductRef`.
     *
     * @param  FixtureProduct  $product
     * @return array{id: int, sku: string, name: string, photo_url: string|null}
     */
    public static function ref(array $product): array
    {
        return [
            'id' => $product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'photo_url' => PreviewFixture::photoUrl($product['sku']),
        ];
    }

    /**
     * `SaleProductOption[]`: every fixture product by name.
     *
     * @return list<array<string, mixed>>
     */
    public static function saleOptions(): array
    {
        $products = PreviewFixture::products();
        usort($products, static fn (array $a, array $b): int => [strtolower($a['name']), $a['id']] <=> [strtolower($b['name']), $b['id']]);

        return array_map(
            static fn (array $product): array => [
                ...self::ref($product),
                'status' => $product['status']->value,
                'selling_price' => PreviewDecimal::scale4($product['selling_price']),
            ],
            $products,
        );
    }

    /**
     * `ProductListItem[]`.
     *
     * @param  list<PreviewProduct>  $products
     * @return list<array<string, mixed>>
     */
    public function listItems(array $products): array
    {
        $lastThirtyDays = PreviewSales::inPeriod(PreviewSales::all(), SalesPeriod::LastThirtyDays);
        $calculator = new SalesCalculator;

        return array_map(
            fn (PreviewProduct $product): array => [
                'id' => $product->id(),
                'sku' => $product->fixture['sku'],
                'name' => $product->name(),
                'category' => $product->fixture['category'],
                'category_label' => $this->categoryName($product->fixture['category']),
                'status' => $product->fixture['status']->value,
                'status_label' => $product->fixture['status']->label(),
                'photo_url' => PreviewFixture::photoUrl($product->fixture['sku']),
                'components_count' => count($product->lines),
                'unit_cost' => $product->costing->cost->unitCost,
                'selling_price' => PreviewDecimal::scale4($product->fixture['selling_price']),
                'margin' => self::margin($product->margin),
                'capacity' => $this->capacity($product),
                'units_sold_last_30_days' => $calculator->summarize(
                    PreviewSales::lines(PreviewSales::ofProduct($lastThirtyDays, $product->id())),
                )->unitsSold,
            ],
            $products,
        );
    }

    /**
     * `Product`.
     *
     * @return array<string, mixed>
     */
    public function detail(PreviewProduct $product): array
    {
        $cost = $product->costing->cost;
        $createdAt = CarbonImmutable::today()->subDays(PreviewFixture::CREATED_DAYS_AGO)->setTime(9, 0)->toIso8601String();

        return [
            'id' => $product->id(),
            'sku' => $product->fixture['sku'],
            'name' => $product->name(),
            'category' => $product->fixture['category'],
            'category_label' => $this->categoryName($product->fixture['category']),
            'status' => $product->fixture['status']->value,
            'status_label' => $product->fixture['status']->label(),
            'description' => $product->fixture['description'],
            'photo_url' => PreviewFixture::photoUrl($product->fixture['sku']),
            'labor_cost' => $cost->laborCost,
            'overhead_cost' => $cost->overheadCost,
            'selling_price' => PreviewDecimal::scale4($product->fixture['selling_price']),
            'cost' => [
                'material_cost' => $cost->materialCost,
                'labor_cost' => $cost->laborCost,
                'overhead_cost' => $cost->overheadCost,
                'unit_cost' => $cost->unitCost,
            ],
            'margin' => self::margin($product->margin),
            'capacity' => $this->capacity($product),
            'components' => $this->components($product),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * `MaterialUsage[]`: the products whose composition uses this material. Bottleneck rows first, then by product name.
     *
     * @return list<array<string, mixed>>
     */
    public function usageOf(Material $material): array
    {
        $calculator = new CostingCalculator;
        $usages = [];

        foreach ($this->all() as $product) {
            $effective = null;
            $covered = null;

            // A material is on one line per product (DB-unique), but the requirements are summed like the domain does.
            foreach ($product->costing->cost->lines as $line) {
                if ($line->materialId === $material->id) {
                    $effective = ($effective ?? BigDecimal::zero())->plus($line->effectiveQuantity);
                    $covered = $line->unitsPossible;
                }
            }

            $capacity = $product->costing->capacity;
            $bottleneck = $capacity->bottleneckMaterialId === null ? null : ($this->materialsById[$capacity->bottleneckMaterialId] ?? null);

            if ($effective === null || $covered === null || $capacity->maxProducible === null || $bottleneck === null) {
                continue;
            }

            $isBottleneck = $bottleneck->id === $material->id;
            $nextLimit = null;

            if ($isBottleneck) {
                $next = $calculator->capacityWithout($product->bom, $material->id);
                $limiting = $next->bottleneckMaterialId === null ? null : ($this->materialsById[$next->bottleneckMaterialId] ?? null);

                // Null when the material is the only one of the composition.
                if ($next->maxProducible !== null && $limiting !== null) {
                    $nextLimit = ['material' => PreviewMaterials::ref($limiting), 'max_producible' => $next->maxProducible];
                }
            }

            $usages[] = [
                'bottleneck' => $isBottleneck,
                'name' => strtolower($product->name()),
                'id' => $product->id(),
                'row' => [
                    'product' => [
                        ...self::ref($product->fixture),
                        'status' => $product->fixture['status']->value,
                        'status_label' => $product->fixture['status']->label(),
                    ],
                    'effective_quantity' => PreviewDecimal::required($effective->toString()),
                    'units_covered' => $covered,
                    'max_producible' => $capacity->maxProducible,
                    'bottleneck' => PreviewMaterials::ref($bottleneck),
                    'is_bottleneck' => $isBottleneck,
                    'next_limit' => $nextLimit,
                ],
            ];
        }

        usort($usages, static fn (array $a, array $b): int => [$b['bottleneck'], $a['name'], $a['id']] <=> [$a['bottleneck'], $b['name'], $b['id']]);

        return array_map(static fn (array $usage): array => $usage['row'], $usages);
    }

    /**
     * `UnproducibleAlert[]`: every product that can't be made, by name, with the stock status of its bottleneck.
     *
     * @return list<array<string, mixed>>
     */
    public function alerts(): array
    {
        $alerts = [];

        foreach (self::byName($this->all()) as $product) {
            $capacity = $product->costing->capacity;
            $bottleneck = $capacity->bottleneckMaterialId === null ? null : ($this->materialsById[$capacity->bottleneckMaterialId] ?? null);

            if ($product->maxProducible() === 0 && $bottleneck !== null) {
                $alerts[] = [
                    'product' => self::ref($product->fixture),
                    'bottleneck' => [...PreviewMaterials::ref($bottleneck), 'stock_status' => $bottleneck->stockStatus()->value],
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param  FixtureProduct  $fixture
     */
    private function resolve(array $fixture): PreviewProduct
    {
        $bom = [];
        $lines = [];

        foreach ($fixture['lines'] as $index => $line) {
            $material = $this->materialsBySku[$line['sku']] ?? null;

            if ($material === null) {
                continue;
            }

            $bom[] = new BomLine(
                materialId: $material->id,
                materialName: $material->name,
                unit: $material->unit->code,
                quantity: $line['quantity'],
                wastePercent: $line['waste_percent'],
                unitCost: $material->unit_cost,
                stockOnHand: $material->quantity_on_hand,
            );

            $lines[] = [
                // Stable per fixture line, even when an earlier line is skipped.
                'id' => $fixture['id'] * 100 + $index + 1,
                // 1-based display order of the lines that are shown.
                'position' => count($lines) + 1,
                'material' => $material,
                'notes' => $line['notes'],
            ];
        }

        $costing = (new CostingCalculator)->calculate($bom, $fixture['labor_cost'], $fixture['overhead_cost']);

        return new PreviewProduct(
            fixture: $fixture,
            lines: $lines,
            bom: $bom,
            costing: $costing,
            margin: (new MarginCalculator)->calculate($fixture['selling_price'], $costing->cost->unitCost),
        );
    }

    /**
     * `Capacity`.
     *
     * @return array{max_producible: int|null, bottleneck: array{id: int, sku: string, name: string, unit: string}|null}
     */
    private function capacity(PreviewProduct $product): array
    {
        $capacity = $product->costing->capacity;
        $bottleneck = $capacity->bottleneckMaterialId === null ? null : ($this->materialsById[$capacity->bottleneckMaterialId] ?? null);

        return [
            'max_producible' => $capacity->maxProducible,
            'bottleneck' => $bottleneck === null ? null : PreviewMaterials::ref($bottleneck),
        ];
    }

    /**
     * The name of a product category code, from the `product_categories` table. The whole table is read once per request,
     * not once per product. A code with no row (impossible for the fixture: the defaults can't be deleted while used) shows as is.
     */
    private function categoryName(string $code): string
    {
        $this->categoryNames ??= ProductCategory::query()->pluck('name', 'code')->all();

        return $this->categoryNames[$code] ?? $code;
    }

    /**
     * `Margin`, or null without a selling price.
     *
     * @return array{amount: string, percent: string|null}|null
     */
    private static function margin(?Margin $margin): ?array
    {
        return $margin === null ? null : ['amount' => $margin->amount, 'percent' => $margin->percent];
    }

    /**
     * `ProductComponentLine[]`.
     *
     * @return list<array<string, mixed>>
     */
    private function components(PreviewProduct $product): array
    {
        $components = [];

        foreach ($product->lines as $index => $line) {
            $cost = $product->costing->cost->lines[$index] ?? throw new LogicException('The costing has one line per displayed line.');
            $material = $line['material'];

            $components[] = [
                'id' => $line['id'],
                'position' => $line['position'],
                'material' => [
                    ...PreviewMaterials::ref($material),
                    'photo_url' => PreviewFixture::photoUrl($material->sku),
                    'stock_status' => $material->stockStatus()->value,
                    'reorder_level' => $material->reorder_level,
                ],
                'quantity' => $cost->quantity,
                'waste_percent' => $cost->wastePercent,
                'effective_quantity' => $cost->effectiveQuantity,
                'unit_cost' => $cost->unitCost,
                'line_cost' => $cost->lineCost,
                'stock_on_hand' => $cost->stockOnHand,
                'units_possible' => $cost->unitsPossible,
                'is_bottleneck' => $cost->isBottleneck,
                'notes' => $line['notes'],
            ];
        }

        return $components;
    }
}
