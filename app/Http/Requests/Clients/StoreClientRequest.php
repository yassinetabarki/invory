<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Domain\Clients\PhoneNumber;
use App\Models\Client;
use App\Services\Clients\ClientMessages;
use App\Validation\ClientFieldRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The Add client window (INV-4, AC-3): Name, Phone and City required, Source optional. A phone another client of the workshop has is
 * refused with "22 345 678 is already the phone of Amira Ben Salah."; her id comes back as the error `phone_taken_by`, so the window
 * offers **Open Amira Ben Salah** (Clients page) or **Choose Amira Ben Salah instead** (Edit order). A name already used is allowed.
 */
class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Client::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ClientFieldRules::rules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ClientFieldRules::messages();
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('phone')) {
                return;
            }

            $phone = PhoneNumber::parse((string) $this->input('phone'));
            $taken = $phone === null ? null : Client::query()->where('phone_key', $phone->key)->first();

            if ($taken !== null) {
                $validator->errors()->add('phone', ClientMessages::phoneTaken($taken->phone, $taken->name));
                $validator->errors()->add('phone_taken_by', (string) $taken->id);
            }
        }];
    }
}
