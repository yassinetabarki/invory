<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\CreateUnit;
use App\Actions\Reference\DeleteUnit;
use App\Actions\Reference\UpdateUnit;
use App\Enums\UnitDimension;
use App\Exceptions\ReferenceInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreUnitRequest;
use App\Http\Requests\Reference\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\Reference\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Units (ADR-0008). Any verified user manages units (decision Q3); every route is authorized through UnitPolicy.
 */
final class UnitController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Unit::class);

        return Inertia::render('settings/units', [
            // `usage_count` = non-deleted materials that use the unit.
            'units' => fn (): array => Unit::query()
                ->withCount('materials')
                ->ordered()
                ->get()
                ->map(fn (Unit $unit): array => [...ReferenceData::unit($unit), 'usage_count' => $unit->materials_count])
                ->all(),
            'dimensions' => UnitDimension::options(),
        ]);
    }

    public function store(StoreUnitRequest $request, CreateUnit $createUnit): RedirectResponse
    {
        $createUnit->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit created.')]);

        return back();
    }

    public function update(UpdateUnitRequest $request, Unit $unit, UpdateUnit $updateUnit): RedirectResponse
    {
        $updateUnit->handle($unit, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return back();
    }

    public function destroy(Unit $unit, DeleteUnit $deleteUnit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        try {
            $deleteUnit->handle($unit);
        } catch (ReferenceInUseException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return back();
    }
}
