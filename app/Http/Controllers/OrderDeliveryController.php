<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\ChangeOrderUnits;
use App\Enums\OrderUnitsChange;
use App\Http\Requests\Orders\OrderCellsRequest;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Mark delivered and its move back on one order line (INV-14-d, AC-3). `{order}` and `{line}` are route-model bound with scoped
 * bindings: a line of another order is a 404. A refusal throws a validation error, which sends the person back to the order's page.
 */
final class OrderDeliveryController extends Controller
{
    public function store(OrderCellsRequest $request, Order $order, OrderLine $line, ChangeOrderUnits $change): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $change->handle($line, OrderUnitsChange::Deliver, $request->cellUnits())]);

        return to_route('orders.show', $order);
    }

    public function destroy(OrderCellsRequest $request, Order $order, OrderLine $line, ChangeOrderUnits $change): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $change->handle($line, OrderUnitsChange::MoveBack, $request->cellUnits())]);

        return to_route('orders.show', $order);
    }
}
