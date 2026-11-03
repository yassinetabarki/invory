<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\TicketSize;
use App\Models\WorkshopLabelSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Save label settings (INV-15-g, AC-7): the ticket size, one of the two in the list. */
final class LabelSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', WorkshopLabelSetting::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['ticket_size' => ['required', Rule::enum(TicketSize::class)]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['ticket_size.*' => __('Choose a ticket size.')];
    }

    public function ticketSize(): TicketSize
    {
        return TicketSize::from($this->string('ticket_size')->value());
    }
}
