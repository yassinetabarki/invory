<?php

declare(strict_types=1);

namespace App\Rules;

use App\Domain\Clients\PhoneNumber;
use App\Services\Clients\ClientMessages;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A phone Invory knows (INV-4, AC-1): eight Tunisian digits however typed, or + and a country code. {@see PhoneNumber} decides.
 */
final class ClientPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumber::parse($value) === null) {
            $fail(ClientMessages::phoneUnknown());
        }
    }
}
