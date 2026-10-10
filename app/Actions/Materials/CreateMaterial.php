<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private AppendStockMovement $appendMovement,
    ) {}

    /** @param array<string, mixed> $data validated input, with `category` and `unit` as codes */
    public function handle(array $data, User $actor): Material
    {
        return DB::transaction(function () use ($data, $actor): Material {
            $material = new Material($this->resolveCodes->handle($data));

            // Audit columns are never mass-assignable: they come from the actor, not from the request.
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
        });
    }
}
