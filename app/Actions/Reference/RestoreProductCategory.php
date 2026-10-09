<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Validation\ValidationException;

/**
 * Puts an archived item back in the pickers. A no-op on an active row: no write, no new reference version (F-30).
 * If another active row took the name a moment ago (a lost race, F-33), the restore fails with the validation error on `name` that the
 * request gives, and the item stays archived.
 */
final readonly class RestoreProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /** @throws ValidationException */
    public function handle(ProductCategory $item): ProductCategory
    {
        if ($item->archived_at === null) {
            return $item;
        }

        ReferenceNameGuard::restoring('product_categories', 'product category', $item, fn (): bool => $item->update(['archived_at' => null]));

        $this->version->bump();

        return $item;
    }
}
