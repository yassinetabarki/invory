<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop edits that workshop's catalogue (ADR-0013, decision Q3). A material of another workshop answers 404, never
 * 403: through HTTP the workshop scope already hides it, this is the second layer.
 */
class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Material $material): Response
    {
        return $this->sameWorkshop($user, $material);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Material $material): Response
    {
        return $this->sameWorkshop($user, $material);
    }

    public function delete(User $user, Material $material): Response
    {
        return $this->sameWorkshop($user, $material);
    }

    private function sameWorkshop(User $user, Material $material): Response
    {
        return $user->workshop_id === $material->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}
