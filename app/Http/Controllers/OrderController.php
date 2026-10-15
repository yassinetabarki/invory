<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\DeleteOrder;
use App\Actions\Orders\RecordOrder;
use App\Actions\Orders\UpdateOrder;
use App\Http\Requests\Orders\IndexOrderRequest;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\OrderProductResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer orders (S-48): Eloquent reads through {@see OrderBook}, `OrderPolicy` on every route and `{order}` route-model bound.
 * Saving goes through the order Actions.
 */
final class OrderController extends Controller
{
    public function index(IndexOrderRequest $request, OrderBook $book): Response
    {
        Gate::authorize('viewAny', Order::class);

        $filters = $request->filters();

        // Every prop that costs a query is a closure, so a partial reload skips what it does not list.
        return Inertia::render('orders/index', [
            'orders' => fn (): AnonymousResourceCollection => OrderResource::collection(
                $book->list($filters['search'])->paginate((int) config('invory.per_page'))->withQueryString(),
            ),
            'filters' => $filters,
        ]);
    }

    public function create(OrderBook $book): Response
    {
        Gate::authorize('create', Order::class);

        return Inertia::render('orders/create', [
            'products' => fn (): AnonymousResourceCollection => OrderProductResource::collection($book->newOrderProducts()),
        ]);
    }

    public function store(StoreOrderRequest $request, RecordOrder $recordOrder): RedirectResponse
    {
        $recordOrder->handle($request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order recorded.')]);

        return to_route('orders.index');
    }

    public function edit(Order $order, OrderBook $book): Response
    {
        Gate::authorize('update', $order);

        return Inertia::render('orders/edit', [
            'order' => fn (): OrderResource => new OrderResource($book->load($order)),
            'products' => fn (): AnonymousResourceCollection => OrderProductResource::collection($book->editOrderProducts($order)),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrder $updateOrder): RedirectResponse
    {
        $updateOrder->handle($order, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('orders.index');
    }

    public function destroy(Order $order, DeleteOrder $deleteOrder): RedirectResponse
    {
        Gate::authorize('delete', $order);

        $deleteOrder->handle($order);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order deleted.')]);

        return back();
    }

    /** The signed-in user, for `orders.user_id`. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }
}
