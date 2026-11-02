<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

/** Query of `GET clients/options` (the client picker, INV-4): `search`, `limit`, `include` (the chosen client's id). */
class ClientOptionsRequest extends FormRequest
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 50;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Client::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'between:1,'.self::MAX_LIMIT],
            'include' => ['nullable', 'array', 'max:'.self::MAX_LIMIT],
            'include.*' => ['integer', 'min:1'],
        ];
    }

    public function term(): ?string
    {
        $search = trim((string) $this->input('search'));

        return $search === '' ? null : $search;
    }

    public function limit(): int
    {
        return $this->integer('limit', self::DEFAULT_LIMIT) ?: self::DEFAULT_LIMIT;
    }

    /** @return list<int> */
    public function included(): array
    {
        $ids = $this->input('include');

        return is_array($ids) ? array_values(array_unique(array_map(intval(...), $ids))) : [];
    }
}
