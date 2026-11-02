<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ClientSummary` in resources/js/types/domain.ts (INV-4): a `ClientRef` with her orders, open orders and last order date. The client
 * must come from `ClientBook::withFigures()` (strict models throw when the figures are missing).
 *
 * @mixin Client
 */
final class ClientSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $last = $this->getAttribute('orders_max_ordered_on');

        return [
            ...(new ClientRefResource($this->resource))->toArray($request),
            'orders' => (int) $this->getAttribute('orders_count'),
            'open_orders' => (int) $this->getAttribute('open_orders_count'),
            // A date column comes back as "2026-10-28" on PostgreSQL and "2026-10-28 00:00:00" on SQLite: the day only.
            'last_order_on' => $last === null ? null : substr((string) $last, 0, 10),
        ];
    }
}
