<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\Size;
use App\Services\Reference\ReferenceData;
use App\Services\Workshops\CurrentWorkshop;
use ArrayObject;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * The ONE reader of a product's sizes (INV-18, ADR-0028): every screen and every save asks it, none reads a category or a list itself.
 * It reads `product_sizes`, once per request: a product's sizes are read once however many readers ask, and a page of many products
 * reads them in ONE query. A writer that holds the product's row lock asks {@see fresh()}, which reads again.
 *
 * A product's sizes are in list order (position, then id). `oneSize` says its only size is the workshop's flagged One size.
 */
final class ProductSizes
{
    private const ATTRIBUTE = 'invory.product_sizes';

    private const ONE_SIZE_ATTRIBUTE = 'invory.workshop_one_size';

    private const WORKSHOP_ATTRIBUTE = 'invory.workshop_sizes';

    private const SUGGESTED_ATTRIBUTE = 'invory.suggested_size_ids';

    /** The sizes of the product, in order; read once per request. */
    public function of(Product $product): ProductSizeSet
    {
        return $this->forProducts([$product])[$product->id];
    }

    /**
     * The sizes of several products, by product id, with one query for the products not read yet; the same cache as {@see of()}.
     *
     * @param  iterable<Product>  $products
     * @return array<int, ProductSizeSet>
     */
    public function forProducts(iterable $products): array
    {
        $cache = $this->cache();
        $wanted = [];

        foreach ($products as $product) {
            $wanted[$product->id] = true;
        }

        $missing = array_values(array_filter(array_keys($wanted), fn (int $id): bool => ! isset($cache[$id])));

        if ($missing !== []) {
            foreach ($this->read($missing) as $id => $set) {
                $cache[$id] = $set;
            }
        }

        return array_map(fn (int $id): ProductSizeSet => $cache[$id], array_combine(array_keys($wanted), array_keys($wanted)));
    }

    /** The sizes of the product read again, for a writer under the product's row lock; the cache keeps the new answer. */
    public function fresh(Product $product): ProductSizeSet
    {
        $set = $this->read([$product->id])[$product->id];
        $cache = $this->cache();
        $cache[$product->id] = $set;

        return $set;
    }

    /**
     * The workshop's flagged One size (what a line recorded before sizes points at), or null when it has none. Read once per request:
     * the read of any product's sizes brings it along, so Edit order's lines cost no query of their own for it.
     */
    public function oneSizeOf(int $workshopId): ?Size
    {
        $cache = $this->oneSizes();

        if (! isset($cache[$workshopId])) {
            $cache[$workshopId] = Size::query()->where('workshop_id', $workshopId)->where('is_one_size', true)->first();
        }

        return $cache[$workshopId];
    }

    /**
     * The workshop's sizes in list order, read once per request. The read of any product's sizes brings the list along (its LEFT JOIN
     * returns every size of the workshop), so a page that shows a product pays no query of its own for it; a page that asked for none
     * reads it here, and it shares that one query with {@see suggestedIds()}.
     *
     * @return Collection<int, Size>
     */
    public function workshop(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::WORKSHOP_ATTRIBUTE)) {
            $this->readWorkshop();
        }

        /** @var Collection<int, Size> */
        return $attributes->get(self::WORKSHOP_ATTRIBUTE);
    }

    /**
     * The ids of the sizes every category suggests in this workshop, by category code (archived categories included), in list order:
     * what `product_category_sizes` holds, or the workshop's One size for a category with no row (ADR-0028 decision 3). One query
     * (shared with {@see workshop()} when that is not read yet), once per request.
     *
     * @return array<string, list<int>>
     */
    public function suggestedIds(): array
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::SUGGESTED_ATTRIBUTE)) {
            $this->readWorkshop();
        }

        /** @var array<int, list<int>> $byCategory */
        $byCategory = $attributes->get(self::SUGGESTED_ATTRIBUTE);
        $oneSize = $this->workshop()->first(fn (Size $size): bool => (bool) $size->is_one_size);
        $suggested = [];

        foreach (app(ReferenceData::class)->productCategories() as $category) {
            $suggested[$category->code] = $byCategory[$category->id] ?? ($oneSize === null ? [] : [$oneSize->id]);
        }

        return $suggested;
    }

    /**
     * The ids of the sizes a new product of this category starts with in this workshop, in list order: what the category suggests there
     * (`product_category_sizes`), or the workshop's One size when it suggests nothing (ADR-0028 decision 3). Used by `CreateProduct`
     * and `ProductFactory` until Add product sends the sizes itself.
     *
     * @return list<int>
     */
    public function startingSizes(int $categoryId, int $workshopId): array
    {
        return CurrentWorkshop::run($workshopId, function () use ($categoryId): array {
            $ids = Size::query()
                ->join('product_category_sizes', 'product_category_sizes.size_id', '=', 'sizes.id')
                ->where('product_category_sizes.product_category_id', $categoryId)
                ->where('product_category_sizes.workshop_id', CurrentWorkshop::id())
                ->ordered()
                ->pluck('sizes.id');

            return array_values(($ids->isNotEmpty() ? $ids : Size::query()->where('is_one_size', true)->pluck('id'))->map(fn (mixed $id): int => (int) $id)->all());
        });
    }

    /**
     * One query: the products' links joined to their sizes, in list order.
     *
     * @param  list<int>  $ids
     * @return array<int, ProductSizeSet> by product id (a product with no size gets an empty set)
     */
    private function read(array $ids): array
    {
        /** @var array<int, Collection<int, Size>> $byProduct */
        $byProduct = [];

        foreach ($ids as $id) {
            $byProduct[$id] = new Collection;
        }

        // The workshop's whole list LEFT JOIN the links of these products: a size no product has comes back once, without a link. That
        // is how the One size row, which a line recorded before sizes needs, comes along in the same query.
        $rows = Size::query()
            ->leftJoin('product_sizes', fn (JoinClause $join) => $join
                ->on('product_sizes.size_id', '=', 'sizes.id')
                ->whereIn('product_sizes.product_id', $ids))
            ->ordered()
            ->get(['sizes.*', 'product_sizes.product_id as linked_product_id']);
        $oneSizes = $this->oneSizes();

        // The rows are the workshop's whole list (once per linked product): keep it for `workshop()`.
        if (! request()->attributes->has(self::WORKSHOP_ATTRIBUTE)) {
            request()->attributes->set(self::WORKSHOP_ATTRIBUTE, $rows->unique('id')->values());
        }

        foreach ($rows as $size) {
            if ($size->is_one_size && ! isset($oneSizes[$size->workshop_id])) {
                $oneSizes[$size->workshop_id] = $size;
            }

            if ($size->getAttribute('linked_product_id') !== null) {
                $byProduct[(int) $size->getAttribute('linked_product_id')]->push($size);
            }
        }

        return array_map(
            fn (Collection $sizes): ProductSizeSet => new ProductSizeSet($sizes->values(), $sizes->count() === 1 && (bool) $sizes->first()?->is_one_size),
            $byProduct,
        );
    }

    /** ONE query: the workshop's sizes LEFT JOIN the categories that suggest them (a size comes once per category, or once without one). */
    private function readWorkshop(): void
    {
        $rows = Size::query()
            ->leftJoin('product_category_sizes', fn (JoinClause $join) => $join
                ->on('product_category_sizes.size_id', '=', 'sizes.id')
                ->where('product_category_sizes.workshop_id', CurrentWorkshop::id()))
            ->ordered()
            ->get(['sizes.*', 'product_category_sizes.product_category_id as suggested_for']);
        $suggested = [];

        foreach ($rows as $row) {
            if ($row->getAttribute('suggested_for') !== null) {
                $suggested[(int) $row->getAttribute('suggested_for')][] = $row->id;
            }
        }

        $attributes = request()->attributes;
        $attributes->set(self::SUGGESTED_ATTRIBUTE, $suggested);

        if (! $attributes->has(self::WORKSHOP_ATTRIBUTE)) {
            $attributes->set(self::WORKSHOP_ATTRIBUTE, $rows->unique('id')->values());
        }
    }

    /** @return ArrayObject<int, Size|null> the flagged One size by workshop id */
    private function oneSizes(): ArrayObject
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::ONE_SIZE_ATTRIBUTE)) {
            $attributes->set(self::ONE_SIZE_ATTRIBUTE, new ArrayObject);
        }

        /** @var ArrayObject<int, Size|null> */
        return $attributes->get(self::ONE_SIZE_ATTRIBUTE);
    }

    /** @return ArrayObject<int, ProductSizeSet> */
    private function cache(): ArrayObject
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::ATTRIBUTE)) {
            $attributes->set(self::ATTRIBUTE, new ArrayObject);
        }

        /** @var ArrayObject<int, ProductSizeSet> */
        return $attributes->get(self::ATTRIBUTE);
    }
}
