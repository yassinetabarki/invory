<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Restocks\RecordRestock;
use App\Http\Requests\Restocks\StoreRestockRequest;
use App\Models\ProductRestock;
use App\Models\User;
use App\Services\Restocks\RestockMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Restocks of finished products (INV-3): the Restocks page with a restock's detail, New restock and saving it (Undo is
 * `RestockUndoController`). `ProductRestockPolicy` on every route. A restock is never edited or deleted.
 */
final class RestockController extends Controller
{
    /** Stub (INV-3-b): the list's props come with INV-3-d. */
    public function index(): Response
    {
        Gate::authorize('viewAny', ProductRestock::class);

        return Inertia::render('restocks/index');
    }

    /** Saving (AC-1, AC-3): the Restocks list with the new restock on top and "34 units restocked.". */
    public function store(StoreRestockRequest $request, RecordRestock $record): RedirectResponse
    {
        $user = $request->user();
        $restock = $record->handle($request->restock(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => RestockMessages::restocked($restock->units)]);

        return to_route('restocks.index');
    }
}
