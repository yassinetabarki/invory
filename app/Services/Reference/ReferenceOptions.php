<?php

declare(strict_types=1);

namespace App\Services\Reference;

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The per-page option lists (`Option[]`: value = code) read from the reference tables: active rows by position then name, plus
 * the one code the record already uses or the filter holds, even when it is archived. Until S-22 moves the pages to the shared
 * `reference` prop, these keep the props of the materials and products pages as they were.
 */
final class ReferenceOptions
{
    /** @return list<array{value: string, label: string}> */
    public function materialCategories(?string $keep = null): array
    {
        $options = [];

        foreach ($this->visible(MaterialCategory::query(), $keep)->get() as $category) {
            $options[] = ['value' => $category->code, 'label' => $category->name];
        }

        return $options;
    }

    /** @return list<array{value: string, label: string}> */
    public function productCategories(?string $keep = null): array
    {
        $options = [];

        foreach ($this->visible(ProductCategory::query(), $keep)->get() as $category) {
            $options[] = ['value' => $category->code, 'label' => $category->name];
        }

        return $options;
    }

    /** @return list<array{value: string, label: string}> labelled "Metre (m)" */
    public function units(?string $keep = null): array
    {
        $options = [];

        foreach ($this->visible(Unit::query(), $keep)->get() as $unit) {
            $options[] = ['value' => $unit->code, 'label' => $unit->label()];
        }

        return $options;
    }

    /**
     * Active rows, plus the row with the kept code. The `or` sits in its own group, so it can't widen the query.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function visible(Builder $query, ?string $keep): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('archived_at')->when($keep !== null, fn (Builder $q) => $q->orWhere('code', $keep)))
            ->orderBy('position')->orderBy('name')->orderBy('id');
    }
}
