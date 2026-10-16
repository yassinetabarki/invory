<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderLineMove;
use App\Http\Requests\Orders\MovePreviewRequest;
use App\Http\Resources\MovePreviewResource;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\Orders\CuttingPreview;

/**
 * The move window's materials table (S-53-c, S-53-d, ADR-0009 rule 5): a small session-authenticated JSON route, like the material picker. It
 * reads the stock saved now without locks; the move itself redoes the maths under the row locks. `{order}` and `{line}` are bound
 * with scoped bindings: a line of another order is a 404.
 */
final class OrderLineMovePreviewController extends Controller
{
    public function show(MovePreviewRequest $request, Order $order, OrderLine $line, CuttingPreview $preview): MovePreviewResource
    {
        return new MovePreviewResource(
            $request->move() === OrderLineMove::BackToStart
                ? $preview->forBackToStart($line, $request->units())
                : $preview->forStartCutting($line, $request->units()),
        );
    }
}
