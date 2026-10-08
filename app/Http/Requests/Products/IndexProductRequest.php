<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production. Checked here so that a 403 comes before any validation error.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-preview') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(ProductCategory::class)],
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

        return [
            'search' => $search === '' ? null : $search,
            'category' => $this->enum('category', ProductCategory::class)?->value,
            'status' => $this->enum('status', ProductStatus::class)?->value,
            'cant_produce' => $this->boolean('cant_produce'),
        ];
    }
}
