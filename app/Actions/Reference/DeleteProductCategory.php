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
     * Only an unused category can be deleted; otherwise archive it. Soft-deleted products count as users (the foreign key would
     * refuse the delete, and restoring them must find their category).
     *
     * @throws ReferenceInUseException
     */
    public function handle(ProductCategory $category): void
    {
        $count = $category->products()->withTrashed()->count();

        if ($count > 0) {
            throw ReferenceInUseException::inUse($category->name, $count, 'product');
        }

        $category->delete();

        $this->version->bump();
    }
}
