<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds a colour the material lacks, at 0 m (INV-21 AC-13): no stock movement, because nothing came in. With cost per colour the colour
 * takes the cost it was given, otherwise it has none. Runs under the material's row lock, so two people adding the same colour, or one
 * adding while another switches the material to one stock, never leave a half-state; {@see MaterialStockWriter} writes the row.
 */
final readonly class AddMaterialColour
{
    public function __construct(private MaterialStockWriter $stock) {}

    /**
     * @param  string|null  $unitCost  the new colour's cost, with cost per colour (the request asks for it); ignored otherwise
     *
     * @throws ValidationException when the material is one stock now, or already has the colour
     */
    public function handle(Material $material, int $colourId, ?string $unitCost, User $actor): MaterialColour
    {
        return DB::transaction(function () use ($material, $colourId, $unitCost, $actor): MaterialColour {
            $this->stock->lock($material);

            if ($material->stock_mode !== MaterialStockMode::PerColour) {
                throw ValidationException::withMessages([
                    'colour_id' => __(':name keeps one stock for all colours.', ['name' => $material->name]),
                ]);
            }

            if ($material->colours->contains(fn (MaterialColour $row): bool => (int) $row->colour_id === $colourId)) {
                throw ValidationException::withMessages([
                    'colour_id' => __('That colour is already on :name.', ['name' => $material->name]),
                ]);
            }

            $material->updated_by = $actor->id;

            return $this->stock->addColour(
                $material,
                $colourId,
                '0',
                $material->cost_mode === MaterialCostMode::PerColour ? $unitCost : null,
            )->load('colour');
        });
    }
}
