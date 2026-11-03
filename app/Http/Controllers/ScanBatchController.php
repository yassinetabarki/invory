<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Production\ScanBatch;
use App\Http\Requests\Production\ScanBatchRequest;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The scan confirm's button (INV-15-c, AC-4): moves the whole batch on, or puts it in stock, then goes to `/scan`, never back to the
 * ticket link, so the next step is only offered by a new scan (ADR-0024 decision 5). A refusal is an error on `moved` or `phases`.
 */
final class ScanBatchController extends Controller
{
    public function store(ScanBatchRequest $request, ProductionBatch $batch, ScanBatch $scan): RedirectResponse
    {
        $user = $request->user();
        $result = $scan->handle($batch, $request->fromStep(), $request->toStep(), $request->units(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => $result['message']]);

        return to_route('scan.index');
    }
}
