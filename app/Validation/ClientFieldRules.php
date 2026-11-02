<?php

declare(strict_types=1);

namespace App\Validation;

use App\Enums\ClientSource;
use App\Rules\ClientPhone;
use App\Services\Clients\ClientMessages;
use Illuminate\Validation\Rule;

/**
 * The rules and messages of a client's fields (INV-4, AC-1), under a prefix: `client.` on New order, none on Add client. Name and City
 * are required only when a new client is made, which the caller decides (`$newClient`); Source is optional, Other source kept to 40.
 */
final readonly class ClientFieldRules
{
    /** @return array<string, mixed> */
    public static function rules(string $prefix = '', bool $newClient = true): array
    {
        $required = $newClient ? 'required' : 'nullable';

        return [
            "{$prefix}phone" => ['bail', 'required', 'string', 'max:40', new ClientPhone],
            "{$prefix}name" => [$required, 'string', 'max:100'],
            "{$prefix}city" => [$required, 'string', 'max:60'],
            "{$prefix}source" => ['nullable', Rule::enum(ClientSource::class)],
            "{$prefix}source_other" => ['nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(string $prefix = ''): array
    {
        return [
            "{$prefix}phone.required" => ClientMessages::phoneRequired(),
            "{$prefix}phone.string" => ClientMessages::phoneUnknown(),
            "{$prefix}phone.max" => ClientMessages::phoneUnknown(),
            "{$prefix}name.required" => ClientMessages::nameRequired(),
            "{$prefix}name.string" => ClientMessages::nameRequired(),
            "{$prefix}name.max" => ClientMessages::nameTooLong(),
            "{$prefix}city.required" => ClientMessages::cityRequired(),
            "{$prefix}city.string" => ClientMessages::cityRequired(),
            "{$prefix}city.max" => ClientMessages::cityTooLong(),
            "{$prefix}source_other.max" => ClientMessages::otherSourceTooLong(),
        ];
    }
}
