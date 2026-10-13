<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Material;
use App\Models\Product;
use App\Services\Costing\ProductCosting;
use App\Services\Costing\ProductCostingService;
use App\Services\Reference\ReferenceData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Every non-deleted product with its composition, costed once per instance: the dashboard's "can't produce" figures.
 *
 * Reads in a constant number of queries (the products, their lines, the materials of those; the units come from
 * {@see ReferenceData::units()}, which the shared `reference` prop reads too) and costs them with
 * {@see ProductCostingService::costMany()}. Costing every product in PHP is accepted until `product_metrics` (S-23, F-38).
 * Nothing is read before the first question, so a partial reload that does not ask for these figures runs no query.
 */
final class ProductCatalog
{
    /** @var Collection<int, Product>|null by name (case-insensitive), then id, each with `components.material` loaded */
    private ?Collection $products = null;

    /** @var array<int, ProductCosting> by product id */
    private array $costings = [];

    /** @var array<int, Product> resolved by {@see self::refs()}, by product id */
    private array $refs = [];

    public function __construct(
        private readonly ProductCostingService $costing,
        private readonly ReferenceData $reference,
    ) {}

    /** Non-deleted products, whatever their status. */
    public function count(): int
    {
        return $this->products()->count();
    }

    /** Products (any status) that can't be made now: `max_producible = 0`. Products without a composition are not counted. */
    public function unproducibleCount(): int
    {
        $this->products();

        return count(array_filter($this->costings, static fn (ProductCosting $costing): bool => $costing->cantProduce()));
    }

    /**
     * Every product that can't be made now, by name, with the material that limits it (its unit loaded, for a `MaterialRef`).
     *
     * @return list<array{product: Product, bottleneck: Material}>
     */
    public function alerts(): array
    {
        $alerts = [];

        foreach ($this->products() as $product) {
            $capacity = $this->costings[$product->id]->result->capacity;

            if ($capacity->maxProducible !== 0) {
                continue;
            }

            foreach ($product->components as $component) {
                if ($component->material_id === $capacity->bottleneckMaterialId) {
                    $alerts[] = ['product' => $product, 'bottleneck' => $component->material];

                    break;
                }
            }
        }

        return $alerts;
    }

    /**
     * The products with these ids, soft-deleted ones included (a sale outlives its product, D-3), by id. Those already loaded
     * cost nothing, so on a full visit (the stats load the catalog first) only the products that were deleted are queried.
     * Ids that match no row are absent.
     *
     * @param  list<int>  $ids
     * @return array<int, Product>
     */
    public function refs(array $ids): array
    {
        $missing = array_values(array_diff(array_unique($ids), array_keys($this->refs)));

        foreach ($this->products ?? [] as $product) {
            if (in_array($product->id, $missing, true)) {
                $this->refs[$product->id] = $product;
            }
        }

        $missing = array_values(array_diff($missing, array_keys($this->refs)));

        if ($missing !== []) {
            foreach (Product::query()->withTrashed()->whereIn('id', $missing)->get(['id', 'sku', 'name', 'photo_path']) as $product) {
                $this->refs[$product->id] = $product;
            }
        }

        return array_intersect_key($this->refs, array_flip($ids));
    }

    /** @return Collection<int, Product> */
    private function products(): Collection
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $products = Product::query()
            ->with(['components.material' => fn ($materials) => $materials->without(['category', 'unit'])])
            ->byName()
            ->get();

        $units = $this->reference->units();

        foreach ($products as $product) {
            foreach ($product->components as $component) {
                $component->material->setRelation('unit', $units->get($component->material->unit_id));
            }
        }

        $this->costings = $this->costing->costMany($products);

        return $this->products = $products;
    }
}
