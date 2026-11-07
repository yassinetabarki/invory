<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use App\Services\Products\ProductSizes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
        private SyncProductColours $syncColours,
        private SyncProductSizes $syncSizes,
        private ProductSizes $productSizes,
        private PhotoStorage $photos,
    ) {}

    /**
     * An uploaded `photo` is stored on the public disk before the transaction and deleted again if it throws.
     *
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code), its `components`, its `colours` (ids), its `sizes` (ids, optional) and the `photo` upload, if any
     */
    public function handle(array $data, User $actor): Product
    {
        return $this->photos->persist('products', $data, null, fn (array $attributes, array $photo): Product => DB::transaction(function () use ($attributes, $photo, $actor): Product {
            $product = (new Product($this->resolveCategory->handle(Arr::except($attributes, ['components', 'colours', 'sizes']))))->forceFill($photo);

            // Audit columns and the workshop are never mass-assignable: they come from the actor, not from the request.
            $product->workshop_id = $actor->workshop_id;
            $product->created_by = $actor->id;
            $product->updated_by = $actor->id;
            $product->save();

            // Only when the input carries colours: a caller that does not mean to change them (a seeder, an older test) keeps them.
            if (array_key_exists('colours', $attributes)) {
                $this->syncColours->handle($product, $attributes['colours']);
            }

            // INV-18-c: the sizes the input names (INV-18-f validates them), else the category's suggestion, else the workshop's One size.
            $this->syncSizes->handle(
                $product,
                array_key_exists('sizes', $attributes) ? $attributes['sizes'] : $this->productSizes->startingSizes($product->category_id, $product->workshop_id),
            );

            $this->syncComponents->handle($product, $attributes['components'] ?? []);

            // Columns the input did not carry (description, selling price, the defaults) are read back as stored.
            return $product->refresh();
        }));
    }
}
