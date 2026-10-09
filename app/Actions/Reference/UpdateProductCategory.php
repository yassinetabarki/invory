<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class UpdateProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /** @param array<string, mixed> $data validated input: `name` and optional `position` (the code never changes) */
    public function handle(ProductCategory $category, array $data): ProductCategory
    {
        $category->update([
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $category->position,
        ]);

        $this->version->bump();

        return $category;
    }
}
