<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\Material;

/**
 * Takes the row lock (`SELECT ... FOR UPDATE` on PostgreSQL) on a material and brings the model up to date with the locked row.
 *
 * Call it first inside the `DB::transaction` of an Action that changes the stock or the unit cost. A route-bound model was
 * read before the transaction began, so a concurrent restock may have changed the balance since: this reads it again under the
 * lock, and every "before" value, every dirty check and every write that follows starts from what is really stored. Other
 * attributes the model carries (a `withCount` result, loaded relations) are kept. A soft-deleted material is no longer found.
 */
final readonly class LockMaterialForUpdate
{
    public function handle(Material $material): Material
    {
        $locked = Material::query()->withoutEagerLoads()->lockForUpdate()->findOrFail($material->id);

        // sync = true: the locked values become the model's "original", so only what the Action changes is dirty.
        $material->setRawAttributes([...$material->getAttributes(), ...$locked->getAttributes()], true);

        return $material;
    }
}
