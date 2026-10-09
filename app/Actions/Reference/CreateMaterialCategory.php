<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class CreateMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /** @param array<string, mixed> $data validated input: `code`, `name`, optional `position` (empty = last) */
    public function handle(array $data): MaterialCategory
    {
        $category = MaterialCategory::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]);

        $this->version->bump();

        return $category;
    }

    private function nextPosition(): int
    {
        return min(((int) MaterialCategory::query()->max('position')) + 1, 32767);
    }
}
