<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a category (ADR-0008). The material and product category requests only name their table and model.
 */
abstract class StoreCategoryRequest extends FormRequest
{
    /** Longest code: the `code` column is `varchar(32)`. */
    public const CODE_MAX = 32;

    /** @return non-empty-string the table, which holds the unique codes and names */
    abstract protected function table(): string;

    /** @return class-string<Model> the model the policy is checked against */
    abstract protected function model(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('create', $this->model()) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Immutable and unique among ALL rows, archived ones included (a code is never reused).
            'code' => ['required', 'string', 'max:'.self::CODE_MAX, 'regex:/^[a-z0-9_]+$/', Rule::unique($this->table(), 'code')],
            'name' => ['required', 'string', 'max:64', Rule::unique($this->table(), 'name')->whereNull('archived_at')],
            'position' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }
}
