<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Enums\ProductStatus;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Unit;
use App\Services\Costing\ProductCosting;
use App\Services\Costing\ProductCostingService;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;

/**
 * MOCK (ADR-0006): an adapter over the REAL products, for the pages that still read their product data from the preview layer
 * (the dashboard, the usage of a material, the product options of the sales form). S-06-BE and S-07-BE replace it with their
 * final services (`product_metrics`, cached aggregates), which deletes this class.
 *
 * `load()` reads every non-deleted product with its composition in a constant number of queries and costs them with
 * ProductCostingService::costMany. Nothing here is a fixture any more except the photo swatches (S-15-BE).
 */
final class PreviewProducts
{
    /**
     * @param  Collection<int, Product>  $products  by name (case-insensitive), then id, each with `components.material` loaded
     * @param  array<int, ProductCosting>  $costings  by product id
     */
    private function __construct(
        private readonly Collection $products,
        private readonly array $costings,
        private readonly ProductCostingService $service,
    ) {}

    /**
     * Every non-deleted product, with its composition, costed: 4 queries however many products there are.
     *
     * @param  Collection<int, Unit>|null  $units  every unit by id when the caller already holds them (the materials of the lines
     *                                             then get theirs from it instead of an eager load of their own), else read here
     */
    public static function load(?Collection $units = null): self
    {
        $service = app(ProductCostingService::class);
        $products = Product::query()
            ->with(['components.material' => fn ($materials) => $materials->without(['category', 'unit'])])
            ->byName()
            ->get();

        $units ??= Unit::query()->get()->keyBy('id');

        foreach ($products as $product) {
            foreach ($product->components as $component) {
                $component->material->setRelation('unit', $units->get($component->material->unit_id));
            }
        }

        return new self($products, $service->costMany($products), $service);
    }

    /**
     * The products, by name then id.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return $this->products;
    }

    /** Products (any status) that can't be made now: `max_producible = 0`. Products without a composition are not counted. */
    public function unproducibleCount(): int
    {
        return count(array_filter($this->costings, static fn (ProductCosting $costing): bool => $costing->cantProduce()));
    }

    /**
     * `ProductRef`.
     *
     * @return array{id: int, sku: string, name: string, photo_url: string|null}
     */
    public static function ref(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'photo_url' => PreviewFixture::photoUrl($product->sku),
        ];
    }

    /**
     * Set `photo_url` on the products, and on the material of each of their lines when the composition is loaded, as the
     * Resources read it. Strict models throw on a missing attribute, so every place that serialises a product calls this first.
     *
     * @param  iterable<Product>  $products
     */
    public static function decorate(iterable $products): void
    {
        foreach ($products as $product) {
            $product->setAttribute('photo_url', PreviewFixture::photoUrl($product->sku));

            if ($product->relationLoaded('components')) {
                PreviewMaterials::decorate($product->components->map(static fn (ProductComponent $component): Material => $component->material));
            }
        }
    }

    /**
     * `SaleProductOption[]`: every non-deleted product by name.
     *
     * @return list<array<string, mixed>>
     */
    public static function saleOptions(): array
    {
        return array_values(Product::query()
            ->byName()
            ->get(['id', 'sku', 'name', 'status', 'selling_price'])
            ->map(static fn (Product $product): array => [
                ...self::ref($product),
                'status' => $product->status->value,
                'selling_price' => $product->selling_price,
            ])
            ->all());
    }

    /** The id when it is an active product (the only ones that can be sold), else null. */
    public static function sellableId(?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        $found = Product::query()->whereKey($id)->where('status', ProductStatus::Active->value)->value('id');

        return is_int($found) ? $found : null;
    }

    /**
     * `MaterialUsage[]`: the products whose composition uses this material. Bottleneck rows first, then by product name.
     *
     * @return list<array<string, mixed>>
     */
    public function usageOf(Material $material): array
    {
        $usages = [];

        foreach ($this->products as $product) {
            $costing = $this->costings[$product->id];
            $materials = self::materialsOf($product);

            // A material is on one line per product (DB-unique), but the requirements are summed like the domain does.
            $effective = null;
            $covered = null;

            foreach ($costing->result->cost->lines as $line) {
                if ($line->materialId === $material->id) {
                    $effective = ($effective ?? BigDecimal::zero())->plus($line->effectiveQuantity);
                    $covered = $line->unitsPossible;
                }
            }

            $capacity = $costing->result->capacity;
            $bottleneck = $capacity->bottleneckMaterialId === null ? null : ($materials[$capacity->bottleneckMaterialId] ?? null);

            if ($effective === null || $covered === null || $capacity->maxProducible === null || $bottleneck === null) {
                continue;
            }

            $isBottleneck = $bottleneck->id === $material->id;
            $nextLimit = null;

            if ($isBottleneck) {
                // Null when the material is the only one of the composition.
                $next = $this->service->nextLimit($costing, $material->id);
                $limiting = $next === null || $next->bottleneckMaterialId === null ? null : ($materials[$next->bottleneckMaterialId] ?? null);

                if ($next !== null && $next->maxProducible !== null && $limiting !== null) {
                    $nextLimit = ['material' => PreviewMaterials::ref($limiting), 'max_producible' => $next->maxProducible];
                }
            }

            $usages[] = [
                'bottleneck' => $isBottleneck,
                'name' => strtolower($product->name),
                'id' => $product->id,
                'row' => [
                    'product' => [
                        ...self::ref($product),
                        'status' => $product->status->value,
                        'status_label' => $product->status->label(),
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

        foreach ($this->products as $product) {
            $capacity = $this->costings[$product->id]->result->capacity;
            $bottleneck = $capacity->bottleneckMaterialId === null ? null : (self::materialsOf($product)[$capacity->bottleneckMaterialId] ?? null);

            if ($capacity->maxProducible === 0 && $bottleneck !== null) {
                $alerts[] = [
                    'product' => self::ref($product),
                    'bottleneck' => [...PreviewMaterials::ref($bottleneck), 'stock_status' => $bottleneck->stockStatus()->value],
                ];
            }
        }

        return $alerts;
    }

    /**
     * The materials of a product's composition by id.
     *
     * @return array<int, Material>
     */
    private static function materialsOf(Product $product): array
    {
        $materials = [];

        foreach ($product->components as $component) {
            $materials[$component->material_id] = $component->material;
        }

        return $materials;
    }
}
