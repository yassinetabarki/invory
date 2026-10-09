<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductStatus;
use App\Mock\PreviewFixture;
use App\Services\Reference\ReferenceCode;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProductRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production. S-04 replaces it with ProductPolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-preview') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(ignoreProductId: null);
    }

    /**
     * Rules shared with UpdateProductRequest. Decimal bounds match the columns: `decimal(12,4)` for costs and prices,
     * `decimal(14,4)` for quantities, `decimal(5,2)` for the waste percentage.
     *
     * `category` is the code of a non-archived product category; on update the product's current code stays valid too.
     *
     * @param  int|null  $ignoreProductId  The product being updated, whose own SKU stays valid.
     * @param  string|null  $currentCategory  The category code the product being updated already has.
     * @return array<string, mixed>
     */
    public static function fieldRules(?int $ignoreProductId, ?string $currentCategory = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', self::uniqueSku($ignoreProductId)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('product_categories', $currentCategory)],
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

    /**
     * MOCK: products have no table yet, so the SKU is checked against the fixture SKUs (S-04 replaces this with
     * `Rule::unique('products')->withoutTrashed()->ignore($product)`).
     */
    private static function uniqueSku(?int $ignoreProductId): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($ignoreProductId): void {
            foreach (PreviewFixture::products() as $product) {
                if ($product['sku'] === $value && $product['id'] !== $ignoreProductId) {
                    $fail('validation.unique')->translate();

                    return;
                }
            }
        };
    }
}
