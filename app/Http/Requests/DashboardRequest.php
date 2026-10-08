<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SalesPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    /**
     * MOCK (ADR-0006): preview-only, denied in production. Checked here so that a 403 comes before any validation error.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-preview') ?? false;
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
