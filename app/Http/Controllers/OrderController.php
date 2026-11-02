<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\DeleteOrder;
use App\Actions\Orders\RecordOrder;
use App\Actions\Orders\UpdateOrder;
use App\Enums\ClientSource;
use App\Enums\OrderStatusFilter;
use App\Exceptions\OrderDeleteRefusedException;
use App\Http\Requests\Orders\IndexOrderRequest;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\ClientRefResource;
use App\Http\Resources\ClientSummaryResource;
use App\Http\Resources\OrderFormProductResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SizeListResource;
use App\Models\Order;
use App\Models\SizeList;
use App\Models\User;
use App\Services\Clients\ClientBook;
use App\Services\Clients\ClientMessages;
use App\Services\Orders\OrderBook;
use App\Services\Products\SizeLists;
use ArrayObject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer orders (S-48): Eloquent reads through {@see OrderBook}, `OrderPolicy` on every route and `{order}` route-model bound.
 * Saving goes through the order Actions; saving lands on the order's own page (S-49).
 */
final class OrderController extends Controller
{
    public function index(IndexOrderRequest $request, OrderBook $book, ClientBook $clients): Response
    {
        Gate::authorize('viewAny', Order::class);

        $filters = $request->filters();
        // INV-4: an id that is not a client of the workshop filters nothing.
        $client = $filters['client'] === null ? null : $clients->find($filters['client']);
        $filters['client'] = $client?->id;

        // Every prop that costs a query is a closure, so a partial reload skips what it does not list.
        return Inertia::render('orders/index', [
            'orders' => fn (): AnonymousResourceCollection => OrderResource::collection(
                $book->list($filters['search'], OrderStatusFilter::from($filters['status']), $filters['client'])->paginate((int) config('invory.per_page'))->withQueryString(),
            ),
            'filters' => $filters,
            // The chosen client, so the filter shows her without loading every client.
            'client_filter' => $client === null ? null : (new ClientRefResource($client))->resolve(),
            'statuses' => fn (): array => OrderStatusFilter::options(),
            // Never follow the search or the filter: a search, filter or page change reloads only `orders` and `filters`.
            'totals' => fn (): array => $book->openTotals(),
            'has_orders' => fn (): bool => $book->hasOrders(),
        ]);
    }

    public function create(Request $request, OrderBook $book, SizeLists $sizes, ClientBook $clients): Response
    {
        Gate::authorize('create', Order::class);

        // From a client page (INV-4, AC-4): `?client=<id>` opens New order with her shown as Existing client. Anything else is ignored.
        $preset = $request->query('client');
        $presetId = is_string($preset) && preg_match('/^[0-9]{1,18}$/', $preset) === 1 ? (int) $preset : null;

        return Inertia::render('orders/create', [
            'products' => fn (): AnonymousResourceCollection => OrderFormProductResource::collection($book->newOrderProducts()),
            'size_lists' => fn (): ArrayObject => $this->sizeLists($sizes),
            'cities' => fn (): array => $clients->cities(),
            'client_sources' => fn (): array => ClientSource::options(),
            'client_preset' => function () use ($clients, $presetId): ?array {
                $client = $presetId === null ? null : $clients->find($presetId);

                return $client === null ? null : (new ClientSummaryResource($client))->resolve();
            },
        ]);
    }

    public function show(Order $order, OrderBook $book): Response
    {
        Gate::authorize('view', $order);

        return Inertia::render('orders/show', [
            'order' => fn (): OrderResource => new OrderResource($book->load($order, withClient: true)),
        ]);
    }

    public function store(StoreOrderRequest $request, RecordOrder $recordOrder): RedirectResponse
    {
        $order = $recordOrder->handle($request->validated(), $this->actor($request));
        $client = $order->clientRecord;

        // INV-4: a new phone made a new client with the order.
        $message = $client !== null && $client->wasRecentlyCreated ? ClientMessages::orderRecordedWithNewClient($client->name) : __('Order recorded.');
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('orders.show', $order);
    }

    public function edit(Order $order, OrderBook $book, SizeLists $sizes, ClientBook $clients): Response
    {
        Gate::authorize('update', $order);

        return Inertia::render('orders/edit', [
            'order' => fn (): OrderResource => new OrderResource($book->load($order)),
            'products' => fn (): AnonymousResourceCollection => OrderFormProductResource::collection($book->editOrderProducts($order)),
            'size_lists' => fn (): ArrayObject => $this->sizeLists($sizes),
            // INV-4: the Add client window over the order asks for the cities when it opens (a partial reload), not on every visit.
            'cities' => Inertia::optional(fn (): array => $clients->cities()),
            'client_sources' => fn (): array => ClientSource::options(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrder $updateOrder): RedirectResponse
    {
        $updateOrder->handle($order, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('orders.show', $order);
    }

    public function destroy(Order $order, DeleteOrder $deleteOrder): RedirectResponse
    {
        Gate::authorize('delete', $order);

        try {
            $deleteOrder->handle($order);
        } catch (OrderDeleteRefusedException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order deleted.')]);

        // Sent from the order's own page, "back" would be a page that no longer exists: land on the list. From the list, stay on it.
        $sentFromItsPage = parse_url(url()->previous(), PHP_URL_PATH) === route('orders.show', $order, false);

        return $sentFromItsPage ? to_route('orders.index') : back();
    }

    /**
     * `size_lists`: the size lists by code, each once however many products use it. Always a JSON object (never `[]`).
     *
     * @return ArrayObject<string, array<mixed>>
     */
    private function sizeLists(SizeLists $sizes): ArrayObject
    {
        return new ArrayObject(array_map(fn (SizeList $list): array => (new SizeListResource($list))->resolve(), $sizes->lists()));
    }

    /** The signed-in user, for `orders.user_id`. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }
}
