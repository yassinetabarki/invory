<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;
use App\Models\User;

final readonly class CreateMaterial
{
    public function __construct(private ResolveReferenceCodes $resolveCodes) {}

    /** @param array<string, mixed> $data validated input, with `category` and `unit` as codes */
    public function handle(array $data, User $actor): Material
    {
        $material = new Material($this->resolveCodes->handle($data));

        // Audit columns are never mass-assignable: they come from the actor, not from the request.
        $material->created_by = $actor->id;
        $material->updated_by = $actor->id;
        $material->save();

        return $material;
    }
}
