<?php

declare(strict_types=1);

namespace App\Http\Requests\Colours;

use App\Models\Colour;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Edit a colour (INV-1): its name, swatch and position. There is no `code` rule, so `validated()` never holds one and a code sent
 * anyway is ignored; the model refuses to save a changed code as well.
 */
class UpdateColourRequest extends FormRequest
{
    /**
     * `{colour}` is route-model bound (another workshop's colour is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $colour = $this->route('colour');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $colour instanceof Colour ? Gate::inspect('update', $colour) : Response::denyAsNotFound();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(StoreColourRequest::normalised($this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $colour = $this->route('colour');

        // Renaming a colour to its own name in other capitals ("INDIGO") is allowed.
        return StoreColourRequest::fieldRules($colour instanceof Colour ? $colour : null);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return StoreColourRequest::fieldMessages();
    }
}
