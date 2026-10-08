<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Enums\SalesPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSaleRequest extends FormRequest
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
            'product_id' => ['nullable', 'integer', 'min:1'],
            'period' => ['nullable', Rule::enum(SalesPeriod::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'record' => ['nullable', 'boolean'],
            'record_product_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function period(): SalesPeriod
    {
        return $this->enum('period', SalesPeriod::class) ?? SalesPeriod::default();
    }

    /** Whether the page opens on the "Record a sale" form. */
    public function record(): bool
    {
        return $this->boolean('record');
    }

    public function recordProductId(): ?int
    {
        return $this->filled('record_product_id') ? $this->integer('record_product_id') : null;
    }

    /**
     * Normalised filters, always all three keys (the `SalesFilters` shape in domain.ts).
     *
     * @return array{search: string|null, product_id: int|null, period: string}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return [
            'search' => $search === '' ? null : $search,
            'product_id' => $this->filled('product_id') ? $this->integer('product_id') : null,
            'period' => $this->period()->value,
        ];
    }
}
