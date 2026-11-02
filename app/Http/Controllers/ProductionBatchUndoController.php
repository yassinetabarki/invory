<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Production\UndoBatch;
use App\Http\Resources\UndoBatchPreviewResource;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionReturns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Undo batch (INV-5-e, AC-6): `show` is the sheet's list of what comes back (JSON, read without locks; ADR-0009 rule 5), `store` undoes
 * it. `{batch}` is route-model bound: another workshop's batch is a 404.
 */
final class ProductionBatchUndoController extends Controller
{
    public function show(ProductionBatch $batch, ProductionReturns $returns): UndoBatchPreviewResource
    {
        Gate::authorize('view', $batch);

        return new UndoBatchPreviewResource($returns->plan($batch));
    }

    public function store(Request $request, ProductionBatch $batch, UndoBatch $undo): RedirectResponse
    {
        Gate::authorize('update', $batch);

        $user = $request->user();
        $undone = $undo->handle($batch, $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => ProductionMessages::undone($undone->label)]);

        return back();
    }
}
