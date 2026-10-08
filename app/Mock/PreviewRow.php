<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Passes an already-shaped array through untouched. Used only as the item resource of a paginated fixture list, so that
 * `PreviewRow::collection($paginator)` serialises to the same `{ data, links, meta }` as a real `XResource::collection($paginator)`.
 *
 * @property array<string, mixed> $resource
 */
final class PreviewRow extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
