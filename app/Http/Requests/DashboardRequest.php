<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SalesPeriod;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    /**
     * The dashboard reads materials, products and sales, so it needs `viewAny` on each (D-4). Checked here so that a 403 comes
     * before any validation error.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('viewAny', Material::class)
            && $user->can('viewAny', Product::class)
            && $user->can('viewAny', Sale::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(SalesPeriod::class)],
        ];
    }

    /** The period the dashboard shows: the request's, or the default (last 30 days). */
    public function period(): SalesPeriod
    {
        return $this->enum('period', SalesPeriod::class) ?? SalesPeriod::default();
    }
}
