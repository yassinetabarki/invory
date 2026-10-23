<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\MoveOrderLineUnits;
use App\Enums\OrderLineMove;
use App\Http\Requests\Orders\MoveOrderLineUnitsRequest;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Moves units of one order line between stages (S-49-b). `{order}` and `{line}` are route-model bound with scoped bindings: a line of
 * another order is a 404. A refused move throws a validation error, which sends the person back to the order's page with it.
 */
final class OrderLineMoveController extends Controller
{
    public function store(MoveOrderLineUnitsRequest $request, Order $order, OrderLine $line, MoveOrderLineUnits $moveUnits): RedirectResponse
    {
        $user = $request->user();

        // A line that counts as one size has one size row: its `units` are the units of that size.
        $units = $request->bySize() ? $request->sizeUnits() : [$line->sizes()->firstOrFail()->size_id => $request->integer('units')];

        $result = $moveUnits->handle(
            $line,
            OrderLineMove::from($request->string('move')->value()),
            $units,
            $user instanceof User ? $user : abort(401),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => $result->message]);

        return to_route('orders.show', $order);
    }
}
