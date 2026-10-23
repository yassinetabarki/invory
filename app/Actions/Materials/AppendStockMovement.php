<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;

/**
 * Appends one row to the stock ledger of a material. The only writer of `stock_movements`: the ledger is append-only, so
 * there is no update and no delete. It does not touch the material and opens no transaction: the calling Action does both,
 * so the balance and its ledger row are written together.
 */
final readonly class AppendStockMovement
{
    /**
     * @param  string  $occurredOn  'Y-m-d': the receipt date of a restock, otherwise the day of the change
     * @param  array<string, string|int|list<array{size_id: int, label: string, units: int}>|null>  $values  the ledger columns of the type (`quantity`, `quantity_before`, ... `note`; a cut also `order_id`, `product_id`, `units` and `sizes`)
     */
    public function handle(Material $material, StockMovementType $type, string $occurredOn, User $actor, array $values = []): StockMovement
    {
        $movement = $material->stockMovements()->make([...$values, 'type' => $type, 'occurred_on' => $occurredOn]);

        // Never mass-assignable: the workshop comes from the material, and who did it from the actor, not from the request.
        $movement->workshop_id = $material->workshop_id;
        $movement->user_id = $actor->id;
        $movement->save();

        return $movement;
    }
}
