<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceVersion;

final readonly class RestoreMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(MaterialCategory $item): MaterialCategory
    {
        $item->update(['archived_at' => null]);

        $this->version->bump();

        return $item;
    }
}
