<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Models\Material;
use App\Services\Reference\ReferenceCode;
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
            // Any code in the table, archived or not: a bookmarked filter on a category that was archived since still works.
            'category' => ['nullable', 'string', ReferenceCode::any('material_categories')],
            'low_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(self::sorts())],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** The category code to filter by, or null. */
    public function category(): ?string
    {
        $code = $this->input('category');

        return is_string($code) && $code !== '' ? $code : null;
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
            'category' => $this->category(),
            'low_stock' => $this->boolean('low_stock'),
            'sort' => $this->input('sort') ?: self::DEFAULT_SORT,
        ];
    }
}
