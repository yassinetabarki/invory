<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderLineMove;
use App\Models\Order;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Move units of an order line (S-49-b). The stage limit is not checked here: it needs the count saved under the lock, so the Action
 * checks it.
 */
final class MoveOrderLineUnitsRequest extends FormRequest
{
    /**
     * `{order}` and `{line}` are route-model bound (an unknown or other workshop's order, or a line of another order, is a 404 before
     * anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $order = $this->route('order');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $order instanceof Order ? Gate::inspect('update', $order) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'move' => ['required', Rule::enum(OrderLineMove::class)],
            'units' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $units = __('Enter a whole number of units, at least 1.');

        return [
            'units.required' => $units,
            'units.integer' => $units,
            'units.min' => $units,
        ];
    }
}
