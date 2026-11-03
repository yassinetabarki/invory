<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/** Settings › Labels (INV-15-g): any member of the workshop reads and changes how its labels and tickets print. */
class WorkshopLabelSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return true;
    }
}
