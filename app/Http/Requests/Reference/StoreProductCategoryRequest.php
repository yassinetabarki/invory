<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Models\ProductCategory;

class StoreProductCategoryRequest extends StoreCategoryRequest
{
    protected function table(): string
    {
        return 'product_categories';
    }

    protected function model(): string
    {
        return ProductCategory::class;
    }
}
