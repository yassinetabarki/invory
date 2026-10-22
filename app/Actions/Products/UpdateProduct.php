<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use App\Services\Products\ProductSizeLock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
        private PhotoStorage $photos,
        private ProductSizeLock $sizeLock,
    ) {}

    /**
     * The composition is ALWAYS replaced by the submitted `components` (F-36): the product form submits its whole state, and a
     * missing or null `components` means an empty composition (a multipart body, sent when a photo is attached, can't carry an
     * empty list). A caller that means to keep the composition must send it.
     *
     * A `photo` upload replaces the photo (it wins over `remove_photo`) and `remove_photo` clears it. The new file is stored
     * before the transaction and deleted again if it throws; the old file is deleted after it committed.
     *
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code), its `components` and `photo` / `remove_photo`
     */
    public function handle(Product $product, array $data, User $actor): Product
    {
        return $this->photos->persist('products', $data, $product->photo_path, fn (array $attributes, array $photo): Product => DB::transaction(function () use ($product, $attributes, $photo, $actor): Product {
            $this->guardSizes($product, $attributes['category'] ?? null);

            $product->fill($this->resolveCategory->handle(Arr::except($attributes, ['components'])))->forceFill($photo);
            $product->updated_by = $actor->id;
            $product->save();

            $this->syncComponents->handle($product, $attributes['components'] ?? []);

            return $product->refresh();
        }));
    }

    /**
     * S-58 / AC-4, the second check: under the product's row lock, so the category it has now is the one compared. Throwing here
     * rolls the transaction back and `PhotoStorage` deletes the file it stored for this save.
     */
    private function guardSizes(Product $product, mixed $categoryCode): void
    {
        if ($categoryCode === null) {
            return;
        }

        $locked = Product::query()->with('category')->lockForUpdate()->findOrFail($product->id);
        $refusal = $this->sizeLock->refusal($locked, ProductCategory::query()->where('code', $categoryCode)->firstOrFail());

        if ($refusal !== null) {
            throw ValidationException::withMessages(['category' => $refusal]);
        }
    }
}
