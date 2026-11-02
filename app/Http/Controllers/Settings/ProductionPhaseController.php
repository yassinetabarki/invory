<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Production\AddProductionPhase;
use App\Actions\Production\RemoveProductionPhase;
use App\Actions\Production\RenameProductionPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\ProductionPhaseRequest;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Services\Production\PhaseHoldings;
use App\Services\Production\ProductionMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings › Production phases (INV-5-b, ADR-0023): the workshop's own phases between In cutting and Put in stock, with what each
 * holds and, when it holds units, why it can't be moved or removed. Move up / Move down is {@see ProductionPhaseOrderController}.
 */
final class ProductionPhaseController extends Controller
{
    public function index(PhaseHoldings $holdings): Response
    {
        Gate::authorize('viewAny', ProductionPhase::class);

        return Inertia::render('settings/production-phases', [
            'phases' => function () use ($holdings): array {
                $held = $holdings->all();

                return ProductionPhase::query()->inOrder()->get()->map(static function (ProductionPhase $phase) use ($held): array {
                    $units = $held[$phase->id] ?? 0;

                    return [
                        'id' => $phase->id,
                        'name' => $phase->name,
                        'position' => $phase->position,
                        'units' => $units,
                        'refusal' => $units > 0 ? ProductionMessages::phaseHolds($phase->name, $units) : null,
                    ];
                })->all();
            },
        ]);
    }

    public function store(ProductionPhaseRequest $request, AddProductionPhase $add): RedirectResponse
    {
        $add->handle($request->name(), self::actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => ProductionMessages::phaseAdded()]);

        return back();
    }

    public function update(ProductionPhaseRequest $request, ProductionPhase $phase, RenameProductionPhase $rename): RedirectResponse
    {
        $rename->handle($phase, $request->name(), self::actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => ProductionMessages::phaseUpdated()]);

        return back();
    }

    public function destroy(Request $request, ProductionPhase $phase, RemoveProductionPhase $remove): RedirectResponse
    {
        Gate::authorize('delete', $phase);

        $remove->handle($phase, self::actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => ProductionMessages::phaseRemoved()]);

        return back();
    }

    private static function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }
}
