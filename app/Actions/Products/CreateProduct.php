<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code) and its `components`
     */
    public function handle(array $data, User $actor): Product
    {
        return DB::transaction(function () use ($data, $actor): Product {
            $product = new Product($this->resolveCategory->handle(Arr::except($data, ['components'])));

            // Audit columns are never mass-assignable: they come from the actor, not from the request.
            $product->created_by = $actor->id;
            $product->updated_by = $actor->id;
            $product->save();

            $this->syncComponents->handle($product, $data['components'] ?? []);

            // Columns the input did not carry (description, selling price, the defaults) are read back as stored.
            return $product->refresh();
        });
    }
}
