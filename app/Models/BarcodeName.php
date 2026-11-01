<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Barcodes\NameKey;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\BarcodeNameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a barcode stands for (INV-8, ADR-0020): one name (as its {@see NameKey}), in the colour and size of the
 * number. One name, colour and size has at most one number; a number can stand for several names after renames. A product owns none of
 * these rows: it looks them up by its `name_key`, so they outlive a deleted product and a colour taken off.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property int $barcode_id
 * @property string $name_key
 * @property int $colour_id The colour of the barcode (PostgreSQL's composite key keeps the two equal).
 * @property int $size_id The size of the barcode.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['barcode_id', 'name_key', 'colour_id', 'size_id'])]
class BarcodeName extends Model
{
    /** @use HasFactory<BarcodeNameFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return BelongsTo<Barcode, $this> */
    public function barcode(): BelongsTo
    {
        return $this->belongsTo(Barcode::class);
    }

    /** @return BelongsTo<Colour, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class);
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
