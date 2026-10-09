<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;

final readonly class DeleteProduct
{
    /**
     * Soft delete. The composition lines stay, for history and restore, and don't count as a use of their materials.
     */
    public function handle(Product $product): void
    {
        $product->delete();
    }
}
