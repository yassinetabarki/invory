<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Models\Size;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Validation\ValidationException;

final readonly class UpdateProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * A name taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name`.
     *
     * `suggested_sizes` (INV-18-i, ADR-0028) replaces the current workshop's suggestion for the category, in the same transaction as the
     * rename: One size alone is stored as no row (no row is One size), other workshops' rows and every product's sizes stay as they are.
     * Without the key the suggestion is left alone.
     *
     * @param  array<string, mixed>  $data  validated input: `name`, optional `position` (the code never changes) and optional `suggested_sizes`
     *
     * @throws ValidationException
     */
    public function handle(ProductCategory $category, array $data): ProductCategory
    {
        ReferenceNameGuard::saving('product_categories', function () use ($category, $data): bool {
            $this->replaceSuggestion($category, $data);

            return $category->update([
                'name' => $data['name'],
                'position' => isset($data['position']) ? (int) $data['position'] : $category->position,
            ]);
        });

        $this->version->bump();

        return $category;
    }

    /** @param  array<string, mixed>  $data */
    private function replaceSuggestion(ProductCategory $category, array $data): void
    {
        if (! isset($data['suggested_sizes']) || ! is_array($data['suggested_sizes'])) {
            return;
        }

        $ids = array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $data['suggested_sizes']));
        $oneSizeAlone = count($ids) === 1 && Size::query()->whereKey($ids)->where('is_one_size', true)->exists();

        $category->suggestedSizes()->syncWithPivotValues($oneSizeAlone ? [] : $ids, ['workshop_id' => CurrentWorkshop::id()]);
    }
}
