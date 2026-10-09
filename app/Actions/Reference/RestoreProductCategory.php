<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class RestoreProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(ProductCategory $item): ProductCategory
    {
        $item->update(['archived_at' => null]);

        $this->version->bump();

        return $item;
    }
}
