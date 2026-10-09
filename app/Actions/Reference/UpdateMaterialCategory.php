<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class UpdateMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /** @param array<string, mixed> $data validated input: `name` and optional `position` (the code never changes) */
    public function handle(MaterialCategory $category, array $data): MaterialCategory
    {
        $category->update([
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $category->position,
        ]);

        $this->version->bump();

        return $category;
    }
}
