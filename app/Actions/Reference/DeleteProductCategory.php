<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Exceptions\ReferenceInUseException;
use App\Models\ProductCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class DeleteProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * Only an unused category can be deleted; otherwise archive it.
     *
     * MOCK (ADR-0006): products have no table yet, so the caller passes how many products use the category (the preview
     * fixture). S-04 replaces the parameter with a count on `products.category_id` (soft-deleted products included).
     *
     * @throws ReferenceInUseException
     */
    public function handle(ProductCategory $category, int $productsUsingIt = 0): void
    {
        if ($productsUsingIt > 0) {
            throw ReferenceInUseException::inUse($category->name, $productsUsingIt, 'product');
        }

        $category->delete();

        $this->version->bump();
    }
}
