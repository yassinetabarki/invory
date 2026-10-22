<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Products\ProductSizeLock;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    /**
     * `{product}` is route-model bound (an unknown or soft-deleted product is a 404 before anything is validated), then the
     * policy decides.
     */
    public function authorize(): Response
    {
        $product = $this->route('product');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $product instanceof Product ? Gate::inspect('update', $product) : Response::denyAsNotFound();
    }

    protected function prepareForValidation(): void
    {
        StoreProductRequest::defaultOverheadToZero($this);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            // An explicit load, not a lazy one: the current category stays valid even if it was archived since.
            $product->loadMissing('category');
        }

        return StoreProductRequest::fieldRules($product instanceof Product ? $product : null);
    }

    /**
     * S-58 / AC-4: a product on an order keeps the sizes of its category. The error sits on `category`, next to any other field
     * errors; it is checked only when the category itself passed (`UpdateProduct` checks again under the row lock).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $product = $this->route('product');
            $code = $this->input('category');

            if (! $product instanceof Product || ! is_string($code) || $validator->errors()->has('category')) {
                return;
            }

            $to = ProductCategory::query()->where('code', $code)->first();
            $refusal = $to === null ? null : app(ProductSizeLock::class)->refusal($product, $to);

            if ($refusal !== null) {
                $validator->errors()->add('category', $refusal);
            }
        }];
    }
}
