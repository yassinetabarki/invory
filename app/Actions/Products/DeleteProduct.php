<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Services\Photos\PhotoStorage;
use Illuminate\Support\Facades\DB;

final readonly class DeleteProduct
{
    public function __construct(private PhotoStorage $photos) {}

    /**
     * Soft delete. The composition lines stay, for history and restore, and don't count as a use of their materials. The finished
     * stock goes with the product (S-56, the human's decision of 2026-10-23), under the product's row lock. The photo path is cleared
     * and its file deleted once the delete committed.
     */
    public function handle(Product $product): void
    {
        $photo = $product->photo_path;

        DB::transaction(function () use ($product): void {
            // The lock `UpdateProduct` and `UpdateFinishedStock` take: a stock save never lands after the rows are gone.
            Product::query()->lockForUpdate()->findOrFail($product->id);
            $product->sizeStocks()->delete();

            if ($product->photo_path !== null) {
                $product->forceFill(['photo_path' => null])->save();
            }

            $product->delete();
        });

        $this->photos->delete($photo);
    }
}
