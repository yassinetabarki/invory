<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

class UpdateMaterialCategoryRequest extends UpdateCategoryRequest
{
    protected function table(): string
    {
        return 'material_categories';
    }

    protected function parameter(): string
    {
        return 'material_category';
    }
}
