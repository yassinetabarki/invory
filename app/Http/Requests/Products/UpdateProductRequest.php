<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Mock\PreviewFixture;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production; an unknown product is a 404 before anything is validated.
     * S-04 replaces it with route model binding and ProductPolicy::update.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('access-preview') ?? false)) {
            return false;
        }

        abort_if(PreviewFixture::product($this->productId()) === null, 404);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // MOCK (ADR-0006): the current category of the fixture product (S-04 reads it from the products table).
        $current = PreviewFixture::product($this->productId())['category'] ?? null;

        return StoreProductRequest::fieldRules(ignoreProductId: $this->productId(), currentCategory: $current);
    }

    /** The `{product}` route parameter (a plain integer id until the products table exists). */
    private function productId(): int
    {
        return (int) $this->route('product');
    }
}
