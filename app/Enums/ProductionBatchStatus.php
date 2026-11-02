<?php

declare(strict_types=1);

namespace App\Enums;

/** Where a production batch is (INV-5, ADR-0023): on Progress, put in stock, or undone while In cutting. */
enum ProductionBatchStatus: string
{
    case InProduction = 'in_production';
    case InStock = 'in_stock';
    case Undone = 'undone';
}
