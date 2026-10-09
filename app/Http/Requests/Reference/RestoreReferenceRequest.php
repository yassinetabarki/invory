<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Rules\UniqueActiveName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Restore an archived unit or category (ADR-0008, F-27). Names are unique among the ACTIVE rows, so a restore is refused with
 * an error on `name` while another active row holds the name, ignoring case (F-32). The row's own name is validated with the same
 * `UniqueActiveName` rule the create and update requests use, so the case rule can't drift.
 */
abstract class RestoreReferenceRequest extends FormRequest
{
    /** @return non-empty-string the table, which holds the unique names */
    abstract protected function table(): string;

    /** @return non-empty-string the name of the route parameter that holds the bound row */
    abstract protected function parameter(): string;

    /** @return non-empty-string what the row is, in the error message: "unit", "material category", "product category" */
    abstract protected function noun(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->item()) ?? false;
    }

    /**
     * The request carries no input: the name checked is the one the row already has.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return ['name' => $this->item()->getAttribute('name')];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Restoring a row that is already active changes nothing, so it must not collide with itself. Same rule as create and
            // update, so the case folding can't drift; the message names the active row in the way, as stored.
            'name' => [new UniqueActiveName($this->table(), $this->item(), UniqueActiveName::restoreTemplate($this->noun()))],
        ];
    }

    private function item(): Model
    {
        $item = $this->route($this->parameter());

        // The route binds the row, or answers 404 before the request is built.
        abort_unless($item instanceof Model, 404);

        return $item;
    }
}
