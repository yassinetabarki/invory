<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

final readonly class UpdateMaterial
{
    public function __construct(private ResolveReferenceCodes $resolveCodes) {}

    /** @param array<string, mixed> $data validated input, with `category` and `unit` as codes */
    public function handle(Material $material, array $data): Material
    {
        $material->update($this->resolveCodes->handle($data));

        // The category and unit relations were loaded before the change.
        return $material->load(['category', 'unit']);
    }
}
