<?php

declare(strict_types=1);

namespace App\Actions\Labels;

use App\Enums\TicketSize;
use App\Models\WorkshopLabelSetting;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;

/**
 * Save label settings (INV-15-g, AC-7, ADR-0024 decision 7): the current workshop's row, made on first save. INV-9 adds the label size
 * and the selling price.
 */
final readonly class SaveLabelSettings
{
    public function handle(TicketSize $ticketSize): WorkshopLabelSetting
    {
        return DB::transaction(static function () use ($ticketSize): WorkshopLabelSetting {
            CurrentWorkshop::id() ?? throw new \LogicException('Label settings need a current workshop.');

            $settings = WorkshopLabelSetting::query()->lockForUpdate()->first() ?? new WorkshopLabelSetting;
            $settings->ticket_size = $ticketSize;
            $settings->save();

            return $settings;
        });
    }
}
