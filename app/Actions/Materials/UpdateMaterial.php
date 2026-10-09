<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;
use App\Models\User;

final readonly class UpdateMaterial
{
    public function __construct(private ResolveReferenceCodes $resolveCodes) {}

    /** @param array<string, mixed> $data validated input, with `category` and `unit` as codes */
    public function handle(Material $material, array $data, User $actor): Material
    {
        $material->fill($this->resolveCodes->handle($data));
        $material->updated_by = $actor->id;
        $material->save();

        // The category and unit relations were loaded before the change.
        return $material->load(['category', 'unit']);
    }
}
