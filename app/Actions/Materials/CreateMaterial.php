<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

final readonly class CreateMaterial
{
    public function __construct(private ResolveReferenceCodes $resolveCodes) {}

    /** @param array<string, mixed> $data validated input, with `category` and `unit` as codes */
    public function handle(array $data): Material
    {
        return Material::create($this->resolveCodes->handle($data));
    }
}
