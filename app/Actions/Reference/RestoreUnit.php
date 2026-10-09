<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\Unit;
use App\Services\Reference\ReferenceVersion;

final readonly class RestoreUnit
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(Unit $item): Unit
    {
        $item->update(['archived_at' => null]);

        $this->version->bump();

        return $item;
    }
}
