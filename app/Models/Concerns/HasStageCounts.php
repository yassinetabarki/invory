<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * The stage figures of an order line and of each of its sizes (ADR-0016): `quantity`, `in_cutting`, `ready_to_ship` and `shipped` are
 * columns, To start and "started" are derived. One place, so a later change of the rule (S-57) reaches both models.
 *
 * @property int $quantity
 * @property int $in_cutting
 * @property int $ready_to_ship
 * @property int $shipped
 * @property-read int $to_start The quantity minus the three counts: whatever has not moved yet.
 * @property-read int $started The three counts added up: the units that have left To start.
 */
trait HasStageCounts
{
    /**
     * To start is never stored, so the four figures can't stop adding up to the quantity.
     *
     * @return Attribute<int, never>
     */
    protected function toStart(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity - $this->started);
    }

    /**
     * The units that have left To start.
     *
     * @return Attribute<int, never>
     */
    protected function started(): Attribute
    {
        return Attribute::get(fn (): int => $this->in_cutting + $this->ready_to_ship + $this->shipped);
    }
}
