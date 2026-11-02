<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Enums\ClientSource;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The Clients page's filters (INV-4, AC-2): `search`, `source`, `page`. */
class IndexClientRequest extends FormRequest
{
    /** Checked here so that a 403 comes before any validation error. */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Client::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', Rule::enum(ClientSource::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always every key (the `ClientFilters` shape in domain.ts).
     *
     * @return array{search: string|null, source: string|null}
     */
    public function filters(): array
    {
        $search = trim((string) $this->input('search'));

        return [
            'search' => $search === '' ? null : $search,
            'source' => ClientSource::tryFrom($this->string('source')->toString())?->value,
        ];
    }
}
