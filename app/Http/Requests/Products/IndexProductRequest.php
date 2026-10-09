<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\Reference\ReferenceCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    /** Checked here so that a 403 comes before any validation error. */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Product::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            // Any code in the table, archived or not: a bookmarked filter on a category that was archived since still works.
            'category' => ['nullable', 'string', ReferenceCode::any('product_categories')],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'cant_produce' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always all four keys (the `ProductFilters` shape in domain.ts).
     *
     * @return array{search: string|null, category: string|null, status: string|null, cant_produce: bool}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));
        $category = $this->input('category');

        return [
            'search' => $search === '' ? null : $search,
            'category' => is_string($category) && $category !== '' ? $category : null,
            'status' => $this->enum('status', ProductStatus::class)?->value,
            'cant_produce' => $this->boolean('cant_produce'),
        ];
    }
}
