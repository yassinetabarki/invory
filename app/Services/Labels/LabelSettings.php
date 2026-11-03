<?php

declare(strict_types=1);

namespace App\Services\Labels;

use App\Enums\TicketSize;
use App\Models\WorkshopLabelSetting;

/** The current workshop's label settings (INV-15-g, ADR-0024 decision 7): the saved row, or the defaults when there is none yet. */
final class LabelSettings
{
    public function ticketSize(): TicketSize
    {
        $saved = WorkshopLabelSetting::query()->first();

        return $saved instanceof WorkshopLabelSetting ? $saved->ticket_size : TicketSize::A4Sheet;
    }
}
