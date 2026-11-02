<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clients\AddClient;
use App\Enums\ClientSource;
use App\Enums\OrderStatusFilter;
use App\Http\Requests\Clients\ClientOrdersRequest;
use App\Http\Requests\Clients\IndexClientRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Resources\ClientRefResource;
use App\Http\Resources\ClientSummaryResource;
use App\Http\Resources\OrderResource;
use App\Models\Client;
use App\Models\User;
use App\Services\Clients\ClientBook;
use App\Services\Orders\OrderBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clients (INV-4): the Clients page, Add client and the client page. Reads through {@see ClientBook} and {@see OrderBook}, `ClientPolicy`
 * on every route, `{client}` route-model bound. A client is never edited or removed, so there is no edit, update or destroy.
 */
final class ClientController extends Controller
{
    public function index(IndexClientRequest $request, ClientBook $clients): Response
    {
        $filters = $request->filters();

        // Every prop that costs a query is a closure, so a partial reload skips what it does not list.
        return Inertia::render('clients/index', [
            'clients' => fn (): AnonymousResourceCollection => ClientSummaryResource::collection(
                $clients->list($filters['search'], ClientSource::tryFrom((string) $filters['source']))
                    ->paginate((int) config('invory.per_page'))->withQueryString(),
            ),
            'filters' => $filters,
            'client_sources' => fn (): array => ClientSource::options(),
            'cities' => fn (): array => $clients->cities(),
            'has_clients' => fn (): bool => $clients->hasClients(),
        ]);
    }

    /**
     * Add client (AC-3): back where the window was opened, with "Client created." and the new client in the flash `client_created`,
     * which Edit order uses to choose her.
     */
    public function store(StoreClientRequest $request, AddClient $addClient): RedirectResponse
    {
        $user = $request->user();
        $client = $addClient->handle($request->validated(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);
        Inertia::flash('client_created', (new ClientRefResource($client))->resolve());

        return back();
    }

    public function show(ClientOrdersRequest $request, Client $client, ClientBook $clients, OrderBook $orders): Response
    {
        $filters = $request->filters();

        return Inertia::render('clients/show', [
            'client' => fn (): array => (new ClientSummaryResource($clients->find($client->id) ?? abort(404)))->resolve(),
            'orders' => fn (): AnonymousResourceCollection => OrderResource::collection(
                $orders->list(null, OrderStatusFilter::from($filters['status']), $client->id)
                    ->paginate((int) config('invory.per_page'))->withQueryString(),
            ),
            'filters' => $filters,
            'statuses' => fn (): array => OrderStatusFilter::options(),
        ]);
    }
}
