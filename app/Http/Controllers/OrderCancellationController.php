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
 * Cancel units on one order line (INV-14-d, AC-4): no stock moves. `{order}` and `{line}` are route-model bound with scoped bindings.
 */
final class OrderCancellationController extends Controller
{
    public function store(OrderCellsRequest $request, Order $order, OrderLine $line, ChangeOrderUnits $change): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $change->handle($line, OrderUnitsChange::Cancel, $request->cellUnits())]);

        return to_route('orders.show', $order);
    }
}
