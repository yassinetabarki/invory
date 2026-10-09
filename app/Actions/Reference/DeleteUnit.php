<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Exceptions\ReferenceInUseException;
use App\Models\Unit;
use App\Services\Reference\ReferenceVersion;

final readonly class DeleteUnit
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * System units can only be archived. A user-made unit can be deleted when no material uses it; soft-deleted materials
     * count as users (the foreign key would refuse the delete, and restoring them must find their unit).
     *
     * @throws ReferenceInUseException
     */
    public function handle(Unit $unit): void
    {
        if ($unit->is_system) {
            throw ReferenceInUseException::systemUnit($unit->label());
        }

        $count = $unit->materials()->withTrashed()->count();

        if ($count > 0) {
            throw ReferenceInUseException::inUse($unit->label(), $count, 'material');
        }

        $unit->delete();

        $this->version->bump();
    }
}
