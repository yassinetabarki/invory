<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

class RestoreMaterialCategoryRequest extends RestoreReferenceRequest
{
    protected function table(): string
    {
        return 'material_categories';
    }

    protected function parameter(): string
    {
        return 'material_category';
    }

    protected function noun(): string
    {
        return 'material category';
    }
}
