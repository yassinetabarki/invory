<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\ArchiveUnit;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Archives a unit (ADR-0008). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class ArchiveUnitController extends Controller
{
    public function __invoke(Unit $unit, ArchiveUnit $action): RedirectResponse
    {
        Gate::authorize('update', $unit);

        $action->handle($unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit archived.')]);

        return back();
    }
}
