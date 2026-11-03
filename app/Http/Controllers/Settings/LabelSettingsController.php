<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Labels\SaveLabelSettings;
use App\Enums\TicketSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LabelSettingsRequest;
use App\Models\WorkshopLabelSetting;
use App\Services\Labels\LabelSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings › Labels (INV-15-g, AC-7, ADR-0024 decision 7): how the workshop's work tickets print. INV-9 adds its label settings to the
 * same page and row.
 */
final class LabelSettingsController extends Controller
{
    public function edit(LabelSettings $settings): Response
    {
        Gate::authorize('viewAny', WorkshopLabelSetting::class);

        return Inertia::render('settings/labels', [
            'ticket_size' => $settings->ticketSize()->value,
            'ticket_sizes' => array_map(static fn (TicketSize $size): array => ['value' => $size->value, 'label' => $size->label()], TicketSize::cases()),
        ]);
    }

    public function update(LabelSettingsRequest $request, SaveLabelSettings $save): RedirectResponse
    {
        $save->handle($request->ticketSize());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Label settings saved.')]);

        return to_route('settings.labels.edit');
    }
}
