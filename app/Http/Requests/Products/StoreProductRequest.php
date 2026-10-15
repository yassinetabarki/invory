<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductStatus;
use App\Models\Material;
use App\Models\Product;
use App\Services\Reference\ReferenceCode;
use App\Validation\WorkshopRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        self::defaultOverheadToZero($this);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(product: null);
    }

    /**
     * A blank overhead is saved as 0 (S-43, D-8). A blank field arrives as null (TrimStrings + ConvertEmptyStringsToNull), a missing
     * key means the same. Any other value, even a wrong one, is left alone so the rules still reject it. Shared with UpdateProductRequest.
     */
    public static function defaultOverheadToZero(FormRequest $request): void
    {
        if ($request->input('overhead_cost') === null) {
            $request->merge(['overhead_cost' => '0']);
        }
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
            'sku' => ['required', 'string', 'max:64', WorkshopRule::unique(Product::class, 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('product_categories', $product?->category->code)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'overhead_cost' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'selling_price' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
            // The composition may be empty (a draft). It is `nullable` rather than `present` because a multipart body
            // (a photo is attached) cannot carry an empty array: the key is simply absent, and on update a missing or null list is an empty
            // composition (F-36: the composition is always replaced).
            'components' => ['nullable', 'array'],
            'components.*.material_id' => ['required', 'integer', 'distinct', WorkshopRule::exists(Material::class)],
            'components.*.quantity' => ['required', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'components.*.waste_percent' => ['nullable', 'decimal:0,2', 'min:0', 'lt:100'],
            'components.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }
}
