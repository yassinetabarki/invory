<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use App\Services\Workshops\CurrentWorkshop;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * The workshop's production lock and batch counter (INV-5, ADR-0023 decision 5 and 6): one row per workshop, made on first use. Every
 * production writer (phases, Start batch, moves, Put in stock, Undo batch) locks it FIRST, so the production writes of one workshop run
 * one after another; product, material and workshop locks are only ever taken after it.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $last_batch_number The last batch number given; 0 before the first.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class ProductionCounter extends Model
{
    use BelongsToWorkshop;

    /** @var array<string, mixed> */
    protected $attributes = ['last_batch_number' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['last_batch_number' => 'integer'];
    }

    /** The current workshop's row, locked FOR UPDATE until the transaction ends; made first when the workshop has none yet. */
    public static function lock(): self
    {
        $workshopId = CurrentWorkshop::id() ?? throw new \LogicException('Production needs a current workshop.');

        self::query()->insertOrIgnore([
            'workshop_id' => $workshopId, 'last_batch_number' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return self::query()->lockForUpdate()->firstOrFail();
    }

    /** The next batch number, saved with the row (call it under {@see self::lock()}). */
    public function next(): int
    {
        $this->last_batch_number++;
        $this->save();

        return $this->last_batch_number;
    }
}
