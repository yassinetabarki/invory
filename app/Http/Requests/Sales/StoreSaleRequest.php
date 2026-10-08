<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Enums\ProductStatus;
use App\Mock\PreviewFixture;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production. S-14-BE replaces it with SalePolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-preview') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(currentProductId: null);
    }

    /**
     * Rules shared with UpdateSaleRequest. `unit_price` fits `decimal(12,4)`.
     *
     * @param  int|null  $currentProductId  The product of the sale being edited: it stays valid even if it is no longer active.
     * @return array<string, mixed>
     */
    public static function fieldRules(?int $currentProductId): array
    {
        return [
            'product_id' => ['bail', 'required', 'integer', self::sellableProduct($currentProductId)],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit_price' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'sold_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * MOCK: products have no table yet, so "exists and is active" reads the fixture products (S-14-BE replaces this with
     * `Rule::exists('products', 'id')->withoutTrashed()->where('status', 'active')`).
     */
    private static function sellableProduct(?int $currentProductId): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($currentProductId): void {
            $product = PreviewFixture::product((int) $value);

            if ($product === null) {
                $fail('validation.exists')->translate();
            } elseif ($product['status'] !== ProductStatus::Active && $product['id'] !== $currentProductId) {
                $fail(__('Only active products can be sold.'));
            }
        };
    }
}
