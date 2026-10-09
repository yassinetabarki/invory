<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Validation\ValidationException;

final readonly class UpdateMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * A name taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name`.
     *
     * @param  array<string, mixed>  $data  validated input: `name` and optional `position` (the code never changes)
     *
     * @throws ValidationException
     */
    public function handle(MaterialCategory $category, array $data): MaterialCategory
    {
        ReferenceNameGuard::saving('material_categories', fn (): bool => $category->update([
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $category->position,
        ]));

        $this->version->bump();

        return $category;
    }
}
