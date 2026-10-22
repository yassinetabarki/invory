<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Models\SizeList;
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
            // Every new category is "One size" (ADR-0015); the two lists of clothes and shoes belong to the seeded codes.
            'size_list_id' => SizeList::query()->where('code', SizeList::ONE_SIZE)->value('id'),
        ]));

        $this->version->bump();

        return $category;
    }

    private function nextPosition(): int
    {
        return min(((int) ProductCategory::query()->max('position')) + 1, 32767);
    }
}
