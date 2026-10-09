<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MaterialCategory;
use App\Models\User;

/**
 * Decision Q3: any signed-in (and verified, by the route middleware) user manages reference data. The policy exists so
 * every route is authorized through one place that a roles story can tighten later.
 */
class MaterialCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Also covers archive and restore. */
    public function update(User $user, MaterialCategory $model): bool
    {
        return true;
    }

    public function delete(User $user, MaterialCategory $model): bool
    {
        return true;
    }
}
