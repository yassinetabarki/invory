<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Production\PrintTicketsRequest;
use App\Models\ProductionBatch;
use App\Services\Labels\LabelSettings;
use App\Services\Production\TicketSheet;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Print tickets (INV-15-b, AC-1, AC-2, ADR-0024 decision 6): the page that prints the work tickets of some batches, opened in a new tab
 * from Progress. A batch of another workshop is a 404, like one that doesn't exist (ADR-0013); a batch that left Progress is left out.
 */
final class ProductionTicketController extends Controller
{
    public function index(PrintTicketsRequest $request, TicketSheet $sheet, LabelSettings $settings): Response
    {
        $ids = $request->ids();

        abort_if(ProductionBatch::query()->whereIn('id', $ids)->count() !== count($ids), 404);

        return Inertia::render('progress/tickets', [
            'tickets' => $sheet->tickets($ids),
            'size' => $settings->ticketSize(),
        ]);
    }
}
