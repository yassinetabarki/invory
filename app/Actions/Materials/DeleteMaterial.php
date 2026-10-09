<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Exceptions\MaterialInUseException;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Support\Facades\DB;

final readonly class DeleteMaterial
{
    /**
     * Soft delete, unless a non-deleted product's composition uses the material. Lines of soft-deleted products don't count:
     * they stay for history, and restoring such a product is validated again.
     *
     * @throws MaterialInUseException
     */
    public function handle(Material $material): void
    {
        DB::transaction(function () use ($material): void {
            // `Product` hides soft-deleted rows, so only live products are listed.
            /** @var list<string> $names */
            $names = Product::query()
                ->whereIn('id', ProductComponent::query()->where('material_id', $material->id)->select('product_id'))
                ->pluck('name')
                ->all();

            if ($names !== []) {
                throw MaterialInUseException::usedBy($material->name, $names);
            }

            $material->delete();
        });
    }
}
