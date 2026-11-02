<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Production\MoveProductionPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\MoveProductionPhaseRequest;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/** Move up / Move down of a production phase (INV-5-b). Its own controller: the arch preset allows only resource methods. */
final class ProductionPhaseOrderController extends Controller
{
    public function update(MoveProductionPhaseRequest $request, ProductionPhase $phase, MoveProductionPhase $move): RedirectResponse
    {
        $user = $request->user();
        $move->handle($phase, $request->direction(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => ProductionMessages::phasesReordered()]);

        return back();
    }
}
