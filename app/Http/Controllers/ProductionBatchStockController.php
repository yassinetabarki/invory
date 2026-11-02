<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Production\PutBatchInStock;
use App\Http\Requests\Production\PutBatchInStockRequest;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Put in stock (INV-5-e, AC-6). `{batch}` is route-model bound: another workshop's batch is a 404. The toast carries **See the
 * restock**, which opens the restock it wrote on the Restocks page.
 */
final class ProductionBatchStockController extends Controller
{
    public function store(PutBatchInStockRequest $request, ProductionBatch $batch, PutBatchInStock $putInStock): RedirectResponse
    {
        $user = $request->user();
        $result = $putInStock->handle($batch, $request->fromPhase(), $request->units(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['message'],
            'action' => ['label' => ProductionMessages::seeTheRestock(), 'href' => route('restocks.index', ['restock' => $result['restock']->id], false)],
        ]);

        return back();
    }
}
