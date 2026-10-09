<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Validation\ValidationException;

final readonly class CreateProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * A name or code taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name` or `code`.
     *
     * @param  array<string, mixed>  $data  validated input: `code`, `name`, optional `position` (empty = last)
     *
     * @throws ValidationException
     */
    public function handle(array $data): ProductCategory
    {
        $category = ReferenceNameGuard::saving('product_categories', fn (): ProductCategory => ProductCategory::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]));

        $this->version->bump();

        return $category;
    }

    private function nextPosition(): int
    {
        return min(((int) ProductCategory::query()->max('position')) + 1, 32767);
    }
}
