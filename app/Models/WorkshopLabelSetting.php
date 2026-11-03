<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketSize;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * How a workshop's labels and work tickets print (INV-15-g, ADR-0024 decision 7): one row per workshop, made on first save. Until
 * then every setting is its default. INV-9 adds the label size and the selling price.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property TicketSize $ticket_size
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['ticket_size'])]
class WorkshopLabelSetting extends Model
{
    use BelongsToWorkshop;

    /** @var array<string, mixed> */
    protected $attributes = ['ticket_size' => 'a4_8'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ticket_size' => TicketSize::class];
    }
}
