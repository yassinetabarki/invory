<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\ProductCategory;
use App\Models\SizeList;
use Illuminate\Database\Eloquent\Collection;

/**
 * The size lists of the product categories (S-58, ADR-0015), for the screens that show them. The categories come with their list
 * and its sizes, read once per request (kept on the request, like `ReferenceData`): three queries however many times a page asks,
 * which lets the product page and the product form share them.
 */
final class SizeLists
{
    private const CATEGORIES_ATTRIBUTE = 'invory.product_categories_with_sizes';

    /** The sizes of a category, in order. */
    public function of(ProductCategory $category): SizeList
    {
        return $this->category($category->id)->sizeList;
    }

    /**
     * The size list of every product category by its code, archived categories included (a product can keep one).
     *
     * @return array<string, SizeList>
     */
    public function byCategoryCode(): array
    {
        return $this->categories()->mapWithKeys(fn (ProductCategory $category): array => [$category->code => $category->sizeList])->all();
    }

    /** The category with this id, carrying its size list and the sizes. */
    public function category(int $id): ProductCategory
    {
        return $this->categories()->get($id) ?? ProductCategory::query()->with('sizeList.sizes')->findOrFail($id);
    }

    /**
     * Every product category by id, archived ones included, each with its size list and its sizes.
     *
     * @return Collection<int, ProductCategory>
     */
    private function categories(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::CATEGORIES_ATTRIBUTE)) {
            $attributes->set(self::CATEGORIES_ATTRIBUTE, ProductCategory::query()->ordered()->with('sizeList.sizes')->get()->keyBy('id'));
        }

        /** @var Collection<int, ProductCategory> */
        return $attributes->get(self::CATEGORIES_ATTRIBUTE);
    }
}
