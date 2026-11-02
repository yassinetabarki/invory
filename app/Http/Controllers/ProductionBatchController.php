<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Production\StartBatch;
use App\Http\Requests\Production\StartBatchRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Start batch (INV-5-c, AC-2): back to the page with the batches started (flash `batches_started`), which the window shows with
 * their numbers. A refusal is a validation error on `product_id` or `units`, and nothing is written.
 */
final class ProductionBatchController extends Controller
{
    public function store(StartBatchRequest $request, StartBatch $start): RedirectResponse
    {
        $user = $request->user();

        Inertia::flash('batches_started', $start->handle($request->productId(), $request->grid(), $user instanceof User ? $user : abort(401)));

        return back();
    }
}
