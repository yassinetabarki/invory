<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private AppendStockMovement $appendMovement,
        private MaterialStockWriter $stock,
        private PhotoStorage $photos,
    ) {}

    /**
     * An uploaded `photo` is stored on the public disk before the transaction and deleted again if it throws.
     *
     * A material kept per colour (`stock_mode` `per_colour`, INV-21) is written with its `colours` rows through the stock writer, and its
     * ledger opens with one `created` entry per colour, all in one entry group.
     *
     * @param  array<string, mixed>  $data  validated input, with `category` and `unit` as codes (and the `photo` upload, if any)
     */
    public function handle(array $data, User $actor): Material
    {
        return $this->photos->persist('materials', $data, null, fn (array $attributes, array $photo): Material => DB::transaction(function () use ($attributes, $photo, $actor): Material {
            $stockMode = MaterialStockMode::tryFrom((string) ($attributes['stock_mode'] ?? '')) ?? MaterialStockMode::Single;
            $costMode = MaterialCostMode::tryFrom((string) ($attributes['cost_mode'] ?? '')) ?? MaterialCostMode::Same;
            $colours = is_array($attributes['colours'] ?? null) ? array_values($attributes['colours']) : [];

            // A per-colour material starts empty: its colour rows give it its total and its cost.
            $material = (new Material([
                'quantity_on_hand' => '0.0000',
                'unit_cost' => '0.0000',
                ...$this->resolveCodes->handle(Arr::except($attributes, ['stock_mode', 'cost_mode', 'colours'])),
            ]))->forceFill($photo);

            // Audit columns, the workshop and the modes are never mass-assignable: they come from the actor and the Action.
            $material->workshop_id = $actor->workshop_id;
            $material->created_by = $actor->id;
            $material->updated_by = $actor->id;
            $material->stock_mode = $stockMode;
            $material->cost_mode = $stockMode === MaterialStockMode::PerColour ? $costMode : MaterialCostMode::Same;
            $material->save();

            if ($stockMode === MaterialStockMode::PerColour) {
                return $this->openColours($material, $colours, $actor);
            }

            // The opening entry of the ledger: the stock and the unit cost the material starts with. A new row has nothing to lock.
            $this->appendMovement->handle($material, StockMovementType::Created, today()->toDateString(), $actor, [
                'quantity' => $material->quantity_on_hand,
                'quantity_after' => $material->quantity_on_hand,
                'unit_price' => $material->unit_cost,
            ]);

            return $material;
        }));
    }

    /**
     * @param  list<mixed>  $colours  validated rows: `colour_id`, `quantity_on_hand` and, with cost per colour, `unit_cost`
     */
    private function openColours(Material $material, array $colours, User $actor): Material
    {
        $this->stock->lock($material);

        $perColourCost = $material->cost_mode === MaterialCostMode::PerColour;
        $group = (string) Str::uuid();

        foreach ($colours as $row) {
            $colourId = (int) Arr::get((array) $row, 'colour_id');
            $cost = $perColourCost ? (string) Arr::get((array) $row, 'unit_cost') : null;

            $stock = $this->stock->addColour($material, $colourId, (string) Arr::get((array) $row, 'quantity_on_hand'), $cost);

            $this->appendMovement->handle($material, StockMovementType::Created, today()->toDateString(), $actor, [
                'quantity' => $stock->quantity_on_hand,
                'quantity_after' => $stock->quantity_on_hand,
                'unit_price' => $stock->unit_cost ?? $material->unit_cost,
                'colour_id' => $colourId,
                'entry_group' => $group,
            ]);
        }

        return $material;
    }
}
