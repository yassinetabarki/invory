<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The Client card's lookup (INV-4, AC-5): by `phone` when Phone loses focus, or by `name` for the "already a client named" note.
 */
class ClientLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Client::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:40', 'required_without:name'],
            'name' => ['nullable', 'string', 'max:100', 'required_without:phone'],
        ];
    }
}
