<?php

declare(strict_types=1);

namespace App\Http\Requests\Restocks;

use App\Models\ProductRestock;
use Illuminate\Foundation\Http\FormRequest;

/** The Restocks page's address (INV-3, AC-2 and AC-5): `search`, `product`, `restock` (the open panel), `page`. */
final class IndexRestockRequest extends FormRequest
{
    /** Checked here so that a 403 comes before any validation error. */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ProductRestock::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'product' => ['nullable', 'integer', 'min:1'],
            'restock' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always every key (the `RestockFilters` shape in domain.ts).
     *
     * @return array{search: string|null, product: int|null, restock: int|null}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return [
            'search' => $search === '' ? null : $search,
            'product' => $this->filled('product') ? $this->integer('product') : null,
            'restock' => $this->filled('restock') ? $this->integer('restock') : null,
        ];
    }
}
