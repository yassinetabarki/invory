<?php

declare(strict_types=1);

namespace App\Services\Clients;

/**
 * Every message about a client's fields (INV-4, AC-1), so New order, Add client and their tests say the same words.
 */
final readonly class ClientMessages
{
    public static function nameRequired(): string
    {
        return __("Enter the client's name.");
    }

    public static function nameTooLong(): string
    {
        return __('Keep the name to 100 characters or fewer.');
    }

    public static function phoneRequired(): string
    {
        return __("Enter the client's phone.");
    }

    public static function phoneUnknown(): string
    {
        return __('Enter 8 digits, such as 22 345 678, or a number abroad starting with +.');
    }

    public static function cityRequired(): string
    {
        return __("Enter the client's city.");
    }

    public static function cityTooLong(): string
    {
        return __('Keep the city to 60 characters or fewer.');
    }

    public static function otherSourceTooLong(): string
    {
        return __('Keep it to 40 characters or fewer.');
    }

    /** "22 345 678 is already the phone of Amira Ben Salah." (Add client, Edit order's window). */
    public static function phoneTaken(string $phone, string $name): string
    {
        return __(':phone is already the phone of :name.', ['phone' => $phone, 'name' => $name]);
    }

    /** The toast of New order when the phone was new. */
    public static function orderRecordedWithNewClient(string $name): string
    {
        return __('Order recorded. :name is now one of your clients.', ['name' => $name]);
    }
}
