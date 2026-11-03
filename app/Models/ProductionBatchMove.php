<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductionBatchMoveKind;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a batch's history (INV-5, ADR-0023): its start, a move forward or back, a Put in stock, its undo. The phase names are
 * kept as text, so removing a phase never breaks the history. Never edited.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $production_batch_id
 * @property ProductionBatchMoveKind $kind
 * @property int $units
 * @property string|null $from_label "In cutting", a phase's name, or null for a start.
 * @property string|null $to_label A phase's name, "In cutting", "Put in stock", or null for an undo.
 * @property bool $scanned Made from the Scan page (INV-15), read by "Your scans today".
 * @property int|null $user_id Never mass-assignable.
 * @property CarbonInterface $created_at
 */
#[Fillable(['production_batch_id', 'kind', 'units', 'from_label', 'to_label', 'scanned'])]
class ProductionBatchMove extends Model
{
    use BelongsToWorkshop;

    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['kind' => ProductionBatchMoveKind::class, 'units' => 'integer', 'scanned' => 'boolean'];
    }

    /** @return BelongsTo<ProductionBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    /** @return BelongsTo<User, $this> who made it; null once their account is deleted */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
