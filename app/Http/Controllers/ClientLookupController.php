<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Clients\ClientLookupRequest;
use App\Http\Resources\ClientSummaryResource;
use App\Services\Clients\ClientBook;
use Illuminate\Http\JsonResponse;

/**
 * The Client card's lookup on New order (INV-4, AC-5; ADR-0009 rule 5): JSON, read-only. `{ client: ClientSummary | null }`, by phone
 * (its key) or by name (ignoring capitals). Only a hint: saving the order finds or adds the client again.
 */
final class ClientLookupController extends Controller
{
    public function show(ClientLookupRequest $request, ClientBook $clients): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $client = $phone !== '' ? $clients->byPhone($phone) : $clients->byName($request->string('name')->toString());

        return response()->json(['client' => $client === null ? null : (new ClientSummaryResource($client))->resolve($request)]);
    }
}
