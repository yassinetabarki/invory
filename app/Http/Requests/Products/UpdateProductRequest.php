<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * `{product}` is route-model bound (an unknown or soft-deleted product is a 404 before anything is validated), then the
     * policy decides.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
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
}
