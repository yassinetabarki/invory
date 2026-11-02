<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Services\Production\ProductionMessages;

/** Put in stock (INV-5-e, AC-6): the phase the page showed the batch in, and how many units. There is no `to_phase`: it is the end. */
final class PutBatchInStockRequest extends MoveBatchRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['to_phase']);

        return $rules;
    }

    protected function atLeastOne(): string
    {
        return ProductionMessages::stockAtLeastOne();
    }
}
