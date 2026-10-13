<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    /**
     * `{sale}` is route-model bound (an unknown sale is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): bool
    {
        $sale = $this->route('sale');

        return $sale instanceof Sale
            && ($this->user()?->can('update', $sale) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $sale = $this->route('sale');

        // The product the sale already has stays valid even once it is no longer active.
        return StoreSaleRequest::fieldRules(currentProductId: $sale instanceof Sale ? $sale->product_id : null);
    }
}
