<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Models\Sale;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSaleRequest extends FormRequest
{
    /**
     * `{sale}` is route-model bound (an unknown sale is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $sale = $this->route('sale');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $sale instanceof Sale ? Gate::inspect('update', $sale) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $sale = $this->route('sale');

        // The product the sale already has stays valid even once it is no longer active.
        return StoreSaleRequest::fieldRules(currentProductId: $sale instanceof Sale ? $sale->product_id : null);
    }
}
