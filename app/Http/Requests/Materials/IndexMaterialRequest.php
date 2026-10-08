<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCategory;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMaterialRequest extends FormRequest
{
    public const DEFAULT_SORT = 'name';

    /**
     * Accepted `sort` values: every Material::SORTABLE column, ascending and prefixed with `-` for descending.
     *
     * @return list<string>
     */
    public static function sorts(): array
    {
        return array_merge(...array_map(
            fn (string $column): array => [$column, "-{$column}"],
            Material::SORTABLE,
        ));
    }

    /**
     * Reads are authorized by the controller (Gate::authorize).
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(MaterialCategory::class)],
            'low_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(self::sorts())],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function category(): ?MaterialCategory
    {
        return $this->enum('category', MaterialCategory::class);
    }

    /**
     * Normalised filters, always all four keys (the `MaterialFilters` shape in domain.ts).
     *
     * @return array{search: string|null, category: string|null, low_stock: bool, sort: string}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return [
            'search' => $search === '' ? null : $search,
            'category' => $this->category()?->value,
            'low_stock' => $this->boolean('low_stock'),
            'sort' => $this->input('sort') ?: self::DEFAULT_SORT,
        ];
    }
}
