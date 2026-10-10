<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `StockMovement` in resources/js/types/domain.ts. Needs the `user_name` attribute that {@see \App\Services\Inventory\StockHistory}
 * joins in. Decimals are scale-4 strings (the model's casts), dates are 'YYYY-MM-DD'.
 *
 * @mixin StockMovement
 */
final class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'occurred_on' => $this->occurred_on->toDateString(),
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            // A price change moves no stock: the contract sends null even though the ledger stores the balance at that moment.
            'quantity_after' => $this->type === StockMovementType::PriceChange ? null : $this->quantity_after,
            'unit_price' => $this->unit_price,
            'unit_cost_before' => $this->unit_cost_before,
            'unit_cost_after' => $this->unit_cost_after,
            'reference' => $this->reference,
            'note' => $this->note,
            'user_name' => $this->user_name,
        ];
    }
}
