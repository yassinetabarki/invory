<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\BarcodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One EAN-13 number of a workshop, made by Invory, for one colour and one size (INV-8, ADR-0020). The names it stands for are its
 * {@see BarcodeName} rows. A number is given once per workshop and never changes; nothing in the app deletes a barcode.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property string $number The 13 digits, check digit included.
 * @property int $colour_id
 * @property int $size_id
 * @property int|null $created_by
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['number', 'colour_id', 'size_id', 'created_by'])]
class Barcode extends Model
{
    /** @use HasFactory<BarcodeFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return HasMany<BarcodeName, $this> */
    public function names(): HasMany
    {
        return $this->hasMany(BarcodeName::class);
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
