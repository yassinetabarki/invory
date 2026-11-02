<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionPhase;
use App\Services\Production\ProductionMessages;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add phase and Edit phase (INV-5-b): a name of 1 to 24 characters (trimmed by the middleware). The rules that need the other phases
 * (a name already used, "In cutting" or "Put in stock", the seventh phase) run in the Actions, under the workshop's row lock.
 */
class ProductionPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phase = $this->route('phase');

        return $phase instanceof ProductionPhase
            ? $this->user()?->can('update', $phase) ?? false
            : $this->user()?->can('create', ProductionPhase::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['bail', 'required', 'string', 'max:'.ProductionPhase::NAME_MAX]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => ProductionMessages::nameRequired(),
            'name.string' => ProductionMessages::nameRequired(),
            'name.max' => ProductionMessages::nameTooLong(),
        ];
    }

    public function name(): string
    {
        return $this->string('name')->value();
    }
}
