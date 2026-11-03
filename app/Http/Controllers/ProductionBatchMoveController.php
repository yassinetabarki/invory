<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Production\MoveBatch;
use App\Http\Requests\Production\MoveBatchRequest;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Move to … / Move back to … of a batch (INV-5-d, AC-5). `{batch}` is route-model bound: another workshop's batch is a 404. A refused
 * move is a validation error on `moved` or `units`, and nothing moves.
 */
final class ProductionBatchMoveController extends Controller
{
    public function store(MoveBatchRequest $request, ProductionBatch $batch, MoveBatch $move): RedirectResponse
    {
        $user = $request->user();
        $result = $move->handle($batch, $request->fromPhase(), $request->toPhase(), $request->units(), $user instanceof User ? $user : abort(401));

        $toast = ['type' => 'success', 'message' => $result['message']];

        // A split (INV-15-b, AC-2): the units moved are on a new batch, with a ticket of its own to print.
        if ($result['batch']->isNot($batch)) {
            $toast['action'] = [
                'label' => ProductionMessages::printTicket($result['batch']->label),
                'href' => route('progress.tickets', ['batches' => [$result['batch']->id]], false),
                'new_tab' => true,
            ];
        }

        Inertia::flash('toast', $toast);

        return back();
    }
}
