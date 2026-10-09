<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Validation\ValidationException;

final readonly class CreateMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * A name or code taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name` or `code`.
     *
     * @param  array<string, mixed>  $data  validated input: `code`, `name`, optional `position` (empty = last)
     *
     * @throws ValidationException
     */
    public function handle(array $data): MaterialCategory
    {
        $category = ReferenceNameGuard::saving('material_categories', fn (): MaterialCategory => MaterialCategory::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]));

        $this->version->bump();

        return $category;
    }

    private function nextPosition(): int
    {
        return min(((int) MaterialCategory::query()->max('position')) + 1, 32767);
    }
}
