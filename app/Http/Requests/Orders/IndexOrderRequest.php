<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class IndexOrderRequest extends FormRequest
{
    /** Checked here so that a 403 comes before any validation error. */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Order::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always every key (the `OrderFilters` shape in domain.ts).
     *
     * @return array{search: string|null}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return ['search' => $search === '' ? null : $search];
    }
}
