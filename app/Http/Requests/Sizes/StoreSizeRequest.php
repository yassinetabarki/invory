<?php

declare(strict_types=1);

namespace App\Http\Requests\Sizes;

use App\Models\Size;
use App\Services\Sizes\SizeClash;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add a size (INV-18-d). Each field answers with the one message of the card, whichever of its rules fails (`bail` stops at the
 * first). The name arrives trimmed (TrimStrings); there is no `is_one_size` rule, so `validated()` never holds it. The update request
 * shares the rules and messages.
 */
class StoreSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Size::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * The rules of name and position, for Add and Edit alike.
     *
     * @return array<string, list<mixed>>
     */
    public static function fieldRules(?Size $ignore = null): array
    {
        return [
            'label' => ['bail', 'required', 'string', 'max:12', self::labelRule($ignore)],
            'position' => ['bail', 'nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * Free in the workshop ignoring capitals (a size may be renamed to its own name in other capitals).
     *
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private static function labelRule(?Size $ignore): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($ignore): void {
            // The other rules report a missing or non-text name.
            if (! is_string($value) || $value === '') {
                return;
            }

            $stored = SizeClash::nameTakenBy($value, $ignore);

            if ($stored !== null) {
                $fail(SizeClash::nameMessage($stored));
            }
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::fieldMessages();
    }

    /** @return array<string, string> */
    public static function fieldMessages(): array
    {
        $label = __("Enter the size's name.");
        $position = __('Enter a whole number from 0 to 32,767, or leave it empty.');

        return [
            'label.required' => $label,
            'label.string' => $label,
            'label.max' => __('Keep the name to 12 characters or fewer.'),
            'position.integer' => $position,
            'position.min' => $position,
            'position.max' => $position,
        ];
    }
}
