<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\Reference\ReferenceCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(product: null);
    }

    /**
     * Rules shared with UpdateProductRequest. Decimal bounds match the columns: `decimal(12,4)` for costs and prices,
     * `decimal(14,4)` for quantities, `decimal(5,2)` for the waste percentage.
     *
     * `sku` is unique among non-deleted products (the product being updated keeps its own). `category` is the code of a
     * non-archived product category; on update the product's current code stays valid too.
     *
     * @param  Product|null  $product  The product being updated (with its `category` loaded), or null when creating.
     * @return array<string, mixed>
     */
    public static function fieldRules(?Product $product): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)->withoutTrashed()],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('product_categories', $product?->category->code)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'overhead_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'selling_price' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
            // The composition may be empty (a draft). It is `nullable` rather than `present` because a multipart body
            // (a photo is attached) cannot carry an empty array: the key is simply absent.
            'components' => ['nullable', 'array'],
            'components.*.material_id' => ['required', 'integer', 'distinct', Rule::exists('materials', 'id')->withoutTrashed()],
            'components.*.quantity' => ['required', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'components.*.waste_percent' => ['nullable', 'decimal:0,2', 'min:0', 'lt:100'],
            'components.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }
}
