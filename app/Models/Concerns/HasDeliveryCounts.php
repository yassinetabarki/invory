<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\OrderProgress;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * The delivery figures of an order line and of each of its colour and size rows (ADR-0026): `quantity`, `delivered` and `cancelled`
 * are columns, To deliver and "settled" are derived, so the three counts always add up to the quantity. One place for both models.
 *
 * @property int $quantity
 * @property int $delivered Units gone to the client: Mark delivered took them off the shelf.
 * @property int $cancelled Units the client dropped: kept on the order, never delivered, no effect on stock.
 * @property-read int $to_deliver The quantity minus the delivered and cancelled units: what the client is waiting for.
 * @property-read int $settled The delivered and cancelled units: what Edit order can't take away.
 */
trait HasDeliveryCounts
{
    /** @return Attribute<int, never> */
    protected function toDeliver(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity - $this->settled);
    }

    /** @return Attribute<int, never> */
    protected function settled(): Attribute
    {
        return Attribute::get(fn (): int => $this->delivered + $this->cancelled);
    }

    /**
     * The status of these counts (INV-14): Cancelled when every unit is cancelled; Delivered when nothing is left to deliver and at
     * least one unit was delivered; Not started while nothing has been delivered; In progress otherwise.
     */
    public static function progressOf(int $quantity, int $delivered, int $cancelled): OrderProgress
    {
        return match (true) {
            $quantity > 0 && $cancelled === $quantity => OrderProgress::Cancelled,
            $delivered + $cancelled >= $quantity && $delivered > 0 => OrderProgress::Delivered,
            $delivered === 0 => OrderProgress::NotStarted,
            default => OrderProgress::InProgress,
        };
    }
}
