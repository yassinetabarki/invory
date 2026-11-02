<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionBatch;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionPath;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Move to … / Move back to … (INV-5-d, AC-5) and Put in stock (INV-5-e, AC-6): the phase the page showed the batch in (`from_phase`,
 * 'cutting' or a phase id), for a move the phase it goes to (`to_phase`), and how many units, a whole number of at least 1. Whether
 * the batch still is there, holds that many and the phases are still in that order is checked by the Action under the locks.
 */
class MoveBatchRequest extends FormRequest
{
    public function authorize(): Response
    {
        $batch = $this->route('batch');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $batch instanceof ProductionBatch ? Gate::inspect('update', $batch) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $ref = ['required', 'regex:/\A(?:cutting|[0-9]{1,18})\z/'];

        return [
            'from_phase' => $ref,
            'to_phase' => $ref,
            'units' => ['bail', 'required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'units.required' => $this->atLeastOne(),
            'units.min' => $this->atLeastOne(),
            'units.integer' => ProductionMessages::wholeUnits(),
            'from_phase.*' => ProductionMessages::movedOn(),
            'to_phase.*' => ProductionMessages::movedOn(),
        ];
    }

    public function fromPhase(): string|int
    {
        return self::ref($this->string('from_phase')->value());
    }

    public function toPhase(): string|int
    {
        return self::ref($this->string('to_phase')->value());
    }

    public function units(): int
    {
        return $this->integer('units');
    }

    protected function atLeastOne(): string
    {
        return ProductionMessages::moveAtLeastOne();
    }

    private static function ref(string $value): string|int
    {
        return $value === ProductionPath::CUTTING ? ProductionPath::CUTTING : (int) $value;
    }
}
