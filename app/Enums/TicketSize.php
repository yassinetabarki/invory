<?php

declare(strict_types=1);

namespace App\Enums;

/** How work tickets print (INV-15, ADR-0024 decision 7): an A4 sheet of 8 (the default) or one per 100 × 60 mm label. */
enum TicketSize: string
{
    case A4Sheet = 'a4_8';
    case Label100x60 = 'label_100x60';

    /** The words of Settings › Labels. */
    public function label(): string
    {
        return match ($this) {
            self::A4Sheet => 'A4 sheet, 8 tickets (2 × 4)',
            self::Label100x60 => '100 × 60 mm, one per label (label printer)',
        };
    }
}
