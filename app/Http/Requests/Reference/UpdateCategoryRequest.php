<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rename or reorder a category (ADR-0008). The code never changes, so sending one is an error.
 */
abstract class UpdateCategoryRequest extends FormRequest
{
    /** @return non-empty-string the table, which holds the unique names */
    abstract protected function table(): string;

    /** @return non-empty-string the name of the route parameter that holds the bound category */
    abstract protected function parameter(): string;

    public function authorize(): bool
    {
        $category = $this->route($this->parameter());

        return $category instanceof Model && ($this->user()?->can('update', $category) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:64', Rule::unique($this->table(), 'name')->whereNull('archived_at')->ignore($this->route($this->parameter()))],
            'position' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }
}
