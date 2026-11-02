<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Enums\OrderStatusFilter;
use App\Models\Client;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** The client page (INV-4, AC-4): her orders' `status` ("All orders" by default here) and `page`. */
class ClientOrdersRequest extends FormRequest
{
    /** `{client}` is route-model bound (another workshop's client is a 404 first), then the policy decides. */
    public function authorize(): Response
    {
        $client = $this->route('client');

        return $client instanceof Client ? Gate::inspect('view', $client) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatusFilter::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{status: string} */
    public function filters(): array
    {
        return ['status' => (OrderStatusFilter::tryFrom($this->string('status')->toString()) ?? OrderStatusFilter::All)->value];
    }
}
