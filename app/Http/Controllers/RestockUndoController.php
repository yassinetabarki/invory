<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Restocks\UndoRestock;
use App\Models\ProductRestock;
use App\Models\User;
use App\Services\Restocks\RestockMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Undo restock (INV-3, AC-6): `POST restocks/{restock}/undo`. `{restock}` is route-model bound (an unknown or other workshop's restock
 * is a 404). Back where the panel was, with "Restock undone."; a refusal is an error on `undo`.
 */
final class RestockUndoController extends Controller
{
    public function store(Request $request, ProductRestock $restock, UndoRestock $undo): RedirectResponse
    {
        Gate::authorize('update', $restock);

        $user = $request->user();
        $undo->handle($restock, $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => RestockMessages::undone()]);

        return back();
    }
}
