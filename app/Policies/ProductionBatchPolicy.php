<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop sees, starts, moves, puts in stock and undoes that workshop's production batches (INV-5). Another workshop's row answers 404, never 403 (ADR-0013): through HTTP the workshop scope already
 * hides it, this is the second layer.
 */
class ProductionBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductionBatch $batch): Response
    {
        return $this->sameWorkshop($user, $batch);
    }

    public function update(User $user, ProductionBatch $batch): Response
    {
        return $this->sameWorkshop($user, $batch);
    }

    public function delete(User $user, ProductionBatch $batch): Response
    {
        return $this->sameWorkshop($user, $batch);
    }

    private function sameWorkshop(User $user, ProductionBatch $batch): Response
    {
        return $user->workshop_id === $batch->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}
