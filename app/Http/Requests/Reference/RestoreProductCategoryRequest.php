<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

class RestoreProductCategoryRequest extends RestoreReferenceRequest
{
    protected function table(): string
    {
        return 'product_categories';
    }

    protected function parameter(): string
    {
        return 'product_category';
    }

    protected function noun(): string
    {
        return 'product category';
    }
}
