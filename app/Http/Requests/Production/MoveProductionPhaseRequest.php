<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Move up / Move down of a phase (INV-5-b). */
class MoveProductionPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phase = $this->route('phase');

        return $phase instanceof ProductionPhase && ($this->user()?->can('update', $phase) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['direction' => ['required', Rule::in(['up', 'down'])]];
    }

    /** @return 'up'|'down' */
    public function direction(): string
    {
        return $this->string('direction')->value() === 'up' ? 'up' : 'down';
    }
}
