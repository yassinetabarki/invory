<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use App\Models\Concerns\HasStageCounts;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineSizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One size of an order line (ADR-0016): how many were ordered and how many of them are in cutting, ready to ship and shipped. The
 * line's own figures are the sums of its rows. Unique per line and size.
 *
 * @property int $id
 * @property int $workshop_id The workshop of the line. Never mass-assignable: it comes from the line.
 * @property int $order_line_id
 * @property int $size_id
 * @property int $quantity Whole units, 1 to 1 000 000.
 * @property int $in_cutting Units of this size in cutting. To start is derived: see `to_start`.
 * @property int $ready_to_ship
 * @property int $shipped The three counts never add up to more than the quantity.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['size_id', 'quantity'])]
class OrderLineSize extends Model
{
    /** @use HasFactory<OrderLineSizeFactory> */
    use BelongsToWorkshop, HasFactory, HasStageCounts;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'in_cutting' => 'integer',
            'ready_to_ship' => 'integer',
            'shipped' => 'integer',
        ];
    }

    /** @return BelongsTo<OrderLine, $this> */
    public function line(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
