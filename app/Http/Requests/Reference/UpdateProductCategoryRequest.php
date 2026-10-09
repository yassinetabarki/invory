<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

class UpdateProductCategoryRequest extends UpdateCategoryRequest
{
    protected function table(): string
    {
        return 'product_categories';
    }

    protected function parameter(): string
    {
        return 'product_category';
    }
}
