<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderLineMove;
use App\Models\Order;
use App\Services\Orders\OrderMessages;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Query of `GET orders/{order}/lines/{line}/moves/preview` (S-53-c): the move window's materials table. Only Start cutting has a
 * table for now. The units the stage holds are not checked: the preview shows what N units would take, and the move checks the limit.
 */
final class MovePreviewRequest extends FormRequest
{
    /** The most units a line can hold (the order's own limit). */
    private const MAX_UNITS = 1_000_000;

    /**
     * `{order}` and `{line}` are route-model bound (an unknown or other workshop's order, or a line of another order, is a 404 before
     * anything is validated), then the policy decides. A preview only reads, so it needs `view`.
     */
    public function authorize(): Response
    {
        $order = $this->route('order');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $order instanceof Order ? Gate::inspect('view', $order) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'move' => ['required', Rule::in([OrderLineMove::StartCutting->value])],
            'units' => ['required', 'integer', 'min:1', 'max:'.self::MAX_UNITS],
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
            'units.max' => OrderMessages::moveLimit(self::MAX_UNITS),
        ];
    }

    public function units(): int
    {
        return $this->integer('units');
    }
}
