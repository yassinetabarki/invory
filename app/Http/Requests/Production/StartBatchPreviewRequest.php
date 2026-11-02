<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionBatch;
use App\Services\Orders\OrderMessages;
use Illuminate\Foundation\Http\FormRequest;

/** Query of `GET progress/products/{product}/materials` (INV-5-c): the units the window's grid adds up to, 1 or more. */
final class StartBatchPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductionBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['units' => ['required', 'integer', 'min:1']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $units = OrderMessages::sizeQuantity();

        return ['units.required' => $units, 'units.integer' => $units, 'units.min' => $units];
    }

    public function units(): int
    {
        return $this->integer('units');
    }
}
