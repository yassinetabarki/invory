<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionBatch;
use App\Services\Production\ProductionPath;
use Illuminate\Foundation\Http\FormRequest;

/** The Progress page's address (INV-5-f, AC-3 and AC-4): the phase shown (none = All phases), the Product and Colour filters, the page. */
final class ProgressIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ProductionBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phase' => ['nullable', 'regex:/\A(?:cutting|[0-9]{1,18})\z/'],
            'product' => ['nullable', 'integer', 'min:1'],
            'colour' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalised filters, always every key (the `ProgressFilters` shape in domain.ts).
     *
     * @return array{phase: string|int|null, product: int|null, colour: int|null}
     */
    public function filters(): array
    {
        $phase = $this->string('phase')->value();

        return [
            'phase' => match ($phase) {
                '' => null,
                ProductionPath::CUTTING => ProductionPath::CUTTING,
                default => (int) $phase,
            },
            'product' => $this->filled('product') ? $this->integer('product') : null,
            'colour' => $this->filled('colour') ? $this->integer('colour') : null,
        ];
    }
}
