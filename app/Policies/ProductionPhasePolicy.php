<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductionPhase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop sees and changes that workshop's production phases (INV-5). Another workshop's row answers 404, never 403 (ADR-0013): through HTTP the workshop scope already
 * hides it, this is the second layer.
 */
class ProductionPhasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductionPhase $phase): Response
    {
        return $this->sameWorkshop($user, $phase);
    }

    public function update(User $user, ProductionPhase $phase): Response
    {
        return $this->sameWorkshop($user, $phase);
    }

    public function delete(User $user, ProductionPhase $phase): Response
    {
        return $this->sameWorkshop($user, $phase);
    }

    private function sameWorkshop(User $user, ProductionPhase $phase): Response
    {
        return $user->workshop_id === $phase->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}
