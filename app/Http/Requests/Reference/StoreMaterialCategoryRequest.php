<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Models\MaterialCategory;

class StoreMaterialCategoryRequest extends StoreCategoryRequest
{
    protected function table(): string
    {
        return 'material_categories';
    }

    protected function model(): string
    {
        return MaterialCategory::class;
    }
}
