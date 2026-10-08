<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

final readonly class UpdateMaterial
{
    /** @param array<string, mixed> $data validated input */
    public function handle(Material $material, array $data): Material
    {
        $material->update($data);

        return $material;
    }
}
