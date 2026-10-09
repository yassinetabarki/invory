<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\ProductCategory;

/**
 * Forms and filters carry the category CODE (`category: 'apparel'`); the table holds an id. The FormRequest has already
 * checked the code, so a code with no row can't reach this point. Input that already has a `category_id` is left alone.
 */
final readonly class ResolveProductCategoryCode
{
    /**
     * @param  array<string, mixed>  $data  validated input
     * @return array<string, mixed> the same data with `category` replaced by `category_id`
     */
    public function handle(array $data): array
    {
        if (array_key_exists('category', $data)) {
            $data['category_id'] = ProductCategory::query()->where('code', $data['category'])->value('id');
            unset($data['category']);
        }

        return $data;
    }
}
