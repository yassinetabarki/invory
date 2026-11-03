<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\ProductionBatch;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Print tickets (INV-15-b, AC-2): `batches[]`, 1 to 50 batch ids in the order to print. Whether each is a batch of the workshop is checked
 * by the controller through the workshop scope, so another workshop's batch is a 404 (ADR-0013).
 */
final class PrintTicketsRequest extends FormRequest
{
    public const MAX = 50;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ProductionBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'batches' => ['required', 'array', 'min:1', 'max:'.self::MAX],
            'batches.*' => ['required', 'regex:/\A[0-9]{1,18}\z/'],
        ];
    }

    /** @return list<int> in the order asked, each once */
    public function ids(): array
    {
        /** @var list<string> $ids */
        $ids = $this->array('batches');

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
