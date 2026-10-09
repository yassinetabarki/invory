<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\RestoreUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\RestoreUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Restores a unit (ADR-0008). Refused with an error on `name` while an active
 * row has the same name (the request). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class RestoreUnitController extends Controller
{
    public function __invoke(RestoreUnitRequest $request, Unit $unit, RestoreUnit $action): RedirectResponse
    {
        $action->handle($unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit restored.')]);

        return back();
    }
}
