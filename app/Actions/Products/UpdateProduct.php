<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
        private PhotoStorage $photos,
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
            $product->fill($this->resolveCategory->handle(Arr::except($attributes, ['components'])))->forceFill($photo);
            $product->updated_by = $actor->id;
            $product->save();

            $this->syncComponents->handle($product, $attributes['components'] ?? []);

            return $product->refresh();
        }));
    }
}
