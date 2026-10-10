<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query of `GET materials/options` (the BOM picker's typeahead): `MaterialOptionsQuery` in resources/js/types/domain.ts.
 */
class MaterialOptionsRequest extends FormRequest
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 50;

    public const MAX_INCLUDE = 50;

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
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'include' => ['nullable', 'array', 'max:'.self::MAX_INCLUDE],
            'include.*' => ['integer', 'min:1'],
        ];
    }

    /** The trimmed search term, or null when there is none. */
    public function term(): ?string
    {
        $search = trim((string) $this->input('search'));

        return $search === '' ? null : $search;
    }

    public function limit(): int
    {
        return $this->integer('limit', self::DEFAULT_LIMIT) ?: self::DEFAULT_LIMIT;
    }

    /**
     * The ids that must be returned whether or not they match the search (the materials already picked), without duplicates.
     *
     * @return list<int>
     */
    public function included(): array
    {
        $ids = $this->input('include');

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map(intval(...), $ids)));
    }
}
