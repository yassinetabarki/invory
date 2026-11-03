<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Services\Production\ScanLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The Scan page's field and camera (INV-15-c, AC-3 to AC-6; ADR-0009 rule 5): what was typed or scanned (`q`: a batch number or a ticket
 * link) → `{ confirm, refusal }`. Read-only. Another workshop's batch is never found, and the answer never says it exists.
 */
final class ScanLookupController extends Controller
{
    public function show(Request $request, ScanLookup $lookup): JsonResponse
    {
        Gate::authorize('viewAny', ProductionBatch::class);
        $q = $request->query('q');

        return response()->json($lookup->find(is_string($q) ? mb_substr($q, 0, 300) : ''));
    }
}
