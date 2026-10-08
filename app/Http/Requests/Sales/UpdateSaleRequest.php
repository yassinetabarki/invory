<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Mock\PreviewFixture;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production; an unknown sale is a 404 before anything is validated.
     * S-14-BE replaces it with route model binding and SalePolicy::update.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('access-preview') ?? false)) {
            return false;
        }

        abort_if($this->sale() === null, 404);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return StoreSaleRequest::fieldRules(currentProductId: $this->sale()['product_id'] ?? null);
    }

    /**
     * The fixture sale of the `{sale}` route parameter (a plain integer id until the sales table exists).
     *
     * @return array{id: int, days_ago: int, product_id: int, quantity: int, note: string|null}|null
     */
    private function sale(): ?array
    {
        return PreviewFixture::sale((int) $this->route('sale'));
    }
}
