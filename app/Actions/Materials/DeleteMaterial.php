<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

final readonly class DeleteMaterial
{
    /**
     * Soft delete. S-04 adds the "material in use" guard.
     */
    public function handle(Material $material): void
    {
        $material->delete();
    }
}
