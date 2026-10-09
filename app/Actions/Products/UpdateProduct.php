<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
    ) {}

    /**
     * The composition is replaced when `components` is submitted (an empty list clears it). Input without the key, or with a null
     * one (a multipart body can't carry an empty list), leaves it alone.
     *
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code) and its `components`
     */
    public function handle(Product $product, array $data, User $actor): Product
    {
        return DB::transaction(function () use ($product, $data, $actor): Product {
            $product->fill($this->resolveCategory->handle(Arr::except($data, ['components'])));
            $product->updated_by = $actor->id;
            $product->save();

            if (isset($data['components'])) {
                $this->syncComponents->handle($product, $data['components']);
            }

            return $product->refresh();
        });
    }
}
