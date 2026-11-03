<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a window of the order page does to units of a line (INV-14, ADR-0026): Mark delivered takes them off the shelf, its move back
 * puts them on it again, Cancel units changes no stock.
 */
enum OrderUnitsChange: string
{
    case Deliver = 'deliver';
    case MoveBack = 'move_back';
    case Cancel = 'cancel';

    /** The verb of "Enter at least one unit to …". */
    public function verb(): string
    {
        return match ($this) {
            self::Deliver => 'mark delivered',
            self::MoveBack => 'move back',
            self::Cancel => 'cancel',
        };
    }
}
