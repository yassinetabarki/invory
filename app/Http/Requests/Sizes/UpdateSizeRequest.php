<?php

declare(strict_types=1);

namespace App\Http\Requests\Sizes;

use App\Models\Size;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Edit a size (INV-18-d): its name and position. There is no `is_one_size` rule, so `validated()` never holds it and a flag sent
 * anyway is ignored.
 */
class UpdateSizeRequest extends FormRequest
{
    /**
     * `{size}` is route-model bound (another workshop's size is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $size = $this->route('size');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $size instanceof Size ? Gate::inspect('update', $size) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $size = $this->route('size');

        // Renaming a size to its own name in other capitals ("xxl") is allowed.
        return StoreSizeRequest::fieldRules($size instanceof Size ? $size : null);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return StoreSizeRequest::fieldMessages();
    }
}
