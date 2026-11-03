<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use App\Models\Concerns\HasDeliveryCounts;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineSizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One colour and size of an order line (ADR-0016, ADR-0025, ADR-0026): how many were ordered and how many of them are delivered and
 * cancelled. The line's own figures are the sums of its rows. Unique per line, colour and size.
 *
 * @property int $id
 * @property int $workshop_id The workshop of the line. Never mass-assignable: it comes from the line.
 * @property int $order_line_id
 * @property int $colour_id The colour ordered (INV-13). Rows recorded before INV-13 have their product's first colour.
 * @property int $size_id
 * @property int $quantity Whole units, 1 to 1 000 000.
 * @property int $delivered Units of this colour and size delivered (INV-14). To deliver is derived: see `to_deliver`.
 * @property int $cancelled The two counts never add up to more than the quantity.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['colour_id', 'size_id', 'quantity'])]
class OrderLineSize extends Model
{
    /** @use HasFactory<OrderLineSizeFactory> */
    use BelongsToWorkshop, HasDeliveryCounts, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'delivered' => 'integer',
            'cancelled' => 'integer',
        ];
    }

    /** @return BelongsTo<OrderLine, $this> */
    public function line(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    /** @return BelongsTo<Colour, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class);
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
