<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Carbon\CarbonInterface;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a material's stock ledger (ARCHITECTURE §4). Append-only: there is no Action that updates or deletes one.
 *
 * Which columns each type fills:
 * - `created`: `quantity` = opening stock, `quantity_after` = the same, `unit_price` = opening unit cost
 * - `restock`: `quantity` received, `quantity_after` = stock after, `unit_price` paid, `reference`
 * - `correction`: `quantity_before` and `quantity_after`, `note` = the reason
 * - `price_change`: `unit_cost_before` and `unit_cost_after`, `quantity_after` = stock at that moment (the props send null for it)
 *
 * @property int $id
 * @property int $material_id
 * @property StockMovementType $type
 * @property CarbonInterface $occurred_on
 * @property string|null $quantity
 * @property string|null $quantity_before
 * @property string|null $quantity_after
 * @property string|null $unit_price
 * @property string|null $unit_cost_before
 * @property string|null $unit_cost_after
 * @property string|null $reference
 * @property string|null $note
 * @property int|null $user_id Who did it (null once that user is deleted). Never mass-assignable: it comes from the actor.
 * @property string|null $user_name Not a column: the history queries join the user's name in (one query, no N+1).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable([
    'type', 'occurred_on', 'quantity', 'quantity_before', 'quantity_after', 'unit_price', 'unit_cost_before', 'unit_cost_after',
    'reference', 'note',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'occurred_on' => 'date',
            'quantity' => 'decimal:4',
            'quantity_before' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'unit_cost_before' => 'decimal:4',
            'unit_cost_after' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
