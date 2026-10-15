<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Illuminate\Support\Facades\DB;

final readonly class CreateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private AppendStockMovement $appendMovement,
        private PhotoStorage $photos,
    ) {}

    /**
     * An uploaded `photo` is stored on the public disk before the transaction and deleted again if it throws.
     *
     * @param  array<string, mixed>  $data  validated input, with `category` and `unit` as codes (and the `photo` upload, if any)
     */
    public function handle(array $data, User $actor): Material
    {
        return $this->photos->persist('materials', $data, null, fn (array $attributes, array $photo): Material => DB::transaction(function () use ($attributes, $photo, $actor): Material {
            $material = (new Material($this->resolveCodes->handle($attributes)))->forceFill($photo);

            // Audit columns and the workshop are never mass-assignable: they come from the actor, not from the request.
            $material->workshop_id = $actor->workshop_id;
            $material->created_by = $actor->id;
            $material->updated_by = $actor->id;
            $material->save();

            // The opening entry of the ledger: the stock and the unit cost the material starts with. A new row has nothing to lock.
            $this->appendMovement->handle($material, StockMovementType::Created, today()->toDateString(), $actor, [
                'quantity' => $material->quantity_on_hand,
                'quantity_after' => $material->quantity_on_hand,
                'unit_price' => $material->unit_cost,
            ]);

            return $material;
        }));
    }
}
