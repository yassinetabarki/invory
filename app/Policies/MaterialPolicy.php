<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

/**
 * MVP: a single company without roles, so every authenticated user may do everything (ADR-0003).
 */
class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Material $material): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Material $material): bool
    {
        return true;
    }

    public function delete(User $user, Material $material): bool
    {
        return true;
    }
}
