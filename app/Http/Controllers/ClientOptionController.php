<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Clients\ClientOptionsRequest;
use App\Http\Resources\ClientRefResource;
use App\Services\Clients\ClientBook;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The client picker's typeahead (INV-4; ADR-0009 rule 5): Find a client by name, Edit order and the Orders list's Client filter. A
 * small session-authenticated JSON route instead of every client in a page prop.
 */
final class ClientOptionController extends Controller
{
    public function index(ClientOptionsRequest $request, ClientBook $clients): AnonymousResourceCollection
    {
        return ClientRefResource::collection($clients->options($request->term(), $request->limit(), $request->included()));
    }
}
