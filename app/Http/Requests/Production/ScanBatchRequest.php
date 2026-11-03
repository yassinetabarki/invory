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
 * The scan confirm's button (INV-15-c, AC-4): what the confirm showed: the step the batch was in (`from`: 'cutting' or a phase id), the
 * step it offered (`to`: 'cutting', a phase id or 'stock') and the units. Whether they still hold is checked by ScanBatch under the locks.
 */
final class ScanBatchRequest extends FormRequest
{
    public function authorize(): Response
    {
        $batch = $this->route('batch');

        // The Gate's own response, so the policy's 404 for another workshop's batch stays a 404 (ADR-0013 decision 5).
        return $batch instanceof ProductionBatch ? Gate::inspect('update', $batch) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'regex:/\A(?:cutting|[0-9]{1,18})\z/'],
            'to' => ['required', 'regex:/\A(?:cutting|stock|[0-9]{1,18})\z/'],
            'units' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['from.*' => ProductionMessages::movedOn(), 'to.*' => ProductionMessages::movedOn(), 'units.*' => ProductionMessages::movedOn()];
    }

    public function fromStep(): string|int
    {
        $value = $this->string('from')->value();

        return $value === ProductionPath::CUTTING ? ProductionPath::CUTTING : (int) $value;
    }

    public function toStep(): string|int
    {
        $value = $this->string('to')->value();

        return in_array($value, [ProductionPath::CUTTING, 'stock'], true) ? $value : (int) $value;
    }

    public function units(): int
    {
        return $this->integer('units');
    }
}
