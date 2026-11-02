<?php

declare(strict_types=1);

namespace App\Enums;

/** What happened to a production batch (INV-5, ADR-0023), one entry of its history. */
enum ProductionBatchMoveKind: string
{
    case Start = 'start';
    case Forward = 'forward';
    case Back = 'back';
    case Stock = 'stock';
    case Undo = 'undo';
}
