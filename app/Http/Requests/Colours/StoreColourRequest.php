<?php

declare(strict_types=1);

namespace App\Http\Requests\Colours;

use App\Models\Colour;
use App\Services\Colours\ColourClash;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add a colour (INV-1). Every field answers with the one message of the card, whichever of its rules fails (`bail` stops at the first).
 * The code and the swatch are put in capitals before validating, and a swatch of six hex digits gets its "#": "stw" is STW,
 * "b7c3cf" is #B7C3CF. The update request shares the rules and messages of name, swatch and position.
 */
class StoreColourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Colour::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalised($this->all()));
    }

    /**
     * The code and the swatch as the rules expect them. Anything else is left as typed, for the rules to refuse.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalised(array $input): array
    {
        $normalised = [];

        if (is_string($input['code'] ?? null)) {
            $normalised['code'] = strtoupper(trim($input['code']));
        }

        if (is_string($input['swatch'] ?? null)) {
            $swatch = strtoupper(trim($input['swatch']));
            $normalised['swatch'] = preg_match('/\A[0-9A-F]{6}\z/', $swatch) === 1 ? "#{$swatch}" : $swatch;
        }

        return $normalised;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['bail', 'required', 'string', 'regex:/\A[A-Z0-9]{2,4}\z/', ColourClash::codeRule()],
            ...self::fieldRules(),
        ];
    }

    /**
     * The rules of name, swatch and position, for Add and Edit alike.
     *
     * @return array<string, list<mixed>>
     */
    public static function fieldRules(?Colour $ignore = null): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:32', ColourClash::nameRule($ignore)],
            'swatch' => ['bail', 'required', 'string', 'regex:/\A#[0-9A-F]{6}\z/'],
            'position' => ['bail', 'nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::fieldMessages() + [
            'code.required' => __('Enter 2 to 4 capital letters or digits, such as IND.'),
            'code.string' => __('Enter 2 to 4 capital letters or digits, such as IND.'),
            'code.regex' => __('Enter 2 to 4 capital letters or digits, such as IND.'),
        ];
    }

    /** @return array<string, string> */
    public static function fieldMessages(): array
    {
        $name = __("Enter the colour's name.");
        $swatch = __('Enter a colour code like #3E4C7E.');
        $position = __('Enter a whole number from 0 to 32,767, or leave it empty.');

        return [
            'name.required' => $name,
            'name.string' => $name,
            'name.max' => __('Keep the name to 32 characters or fewer.'),
            'swatch.required' => $swatch,
            'swatch.string' => $swatch,
            'swatch.regex' => $swatch,
            'position.integer' => $position,
            'position.min' => $position,
            'position.max' => $position,
        ];
    }
}
