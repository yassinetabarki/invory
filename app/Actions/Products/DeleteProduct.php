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
     * Soft delete. The composition lines stay, for history and restore, and don't count as a use of their materials. The photo
     * path is cleared and its file deleted once the delete committed.
     */
    public function handle(Product $product): void
    {
        $photo = $product->photo_path;

        DB::transaction(function () use ($product): void {
            if ($product->photo_path !== null) {
                $product->forceFill(['photo_path' => null])->save();
            }

            $product->delete();
        });

        $this->photos->delete($photo);
    }
}
