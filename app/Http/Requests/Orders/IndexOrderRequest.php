<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatusFilter;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['nullable', Rule::enum(OrderStatusFilter::class)],
            'client' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always every key (the `OrderFilters` shape in domain.ts).
     *
     * `client` is the id asked for (INV-4, AC-7); the controller drops one that is not a client of the workshop.
     *
     * @return array{search: string|null, status: string, client: int|null}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return [
            'search' => $search === '' ? null : $search,
            'status' => (OrderStatusFilter::tryFrom($this->string('status')->toString()) ?? OrderStatusFilter::default())->value,
            'client' => $this->filled('client') ? $this->integer('client') : null,
        ];
    }
}
