<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How an order line counts its units (S-60, ADR-0016). Worked out by `OrderLineSizing`, never stored.
 */
enum OrderLineSizeMode: string
{
    /** The line's size list has several sizes: a quantity and a move per size. */
    case BySize = 'by_size';

    /** A "One size" product: one "Quantity" field and one "How many units?" field, as before. */
    case OneSize = 'one_size';

    /** Recorded before sizes: kept as "One size" whatever the product's sizes (S-60 AC-5). */
    case BeforeSizes = 'before_sizes';
}
