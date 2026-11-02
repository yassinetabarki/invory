<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ClientRef` in resources/js/types/domain.ts (INV-4): who a client is, as every client line, picker option and chosen client shows.
 *
 * @mixin Client
 */
final class ClientRefResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->city,
            'source' => $this->source?->value,
            'source_label' => $this->source?->label(),
            'source_other' => $this->source_other,
        ];
    }
}
