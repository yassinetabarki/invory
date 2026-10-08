<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

final readonly class CreateMaterial
{
    /** @param array<string, mixed> $data validated input */
    public function handle(array $data): Material
    {
        return Material::create($data);
    }
}
