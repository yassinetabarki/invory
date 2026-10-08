<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Paginates a fixture list (already filtered and sorted) like `->paginate()->withQueryString()` does for a real query.
 */
final class PreviewPage
{
    /**
     * Query parameters that only drive what a page opens with: they are not carried into the pagination links, so that
     * going to page 2 does not reopen a "Record a sale" panel.
     */
    private const TRANSIENT_QUERY = ['record', 'record_product_id'];

    /**
     * @param  list<array<string, mixed>>  $items  Every row of the filtered list, already shaped like the contract.
     * @param  string  $path  The URL of the list (without query string), which Laravel puts in `meta.path`.
     */
    public static function paginate(array $items, Request $request, string $path): AnonymousResourceCollection
    {
        $perPage = (int) config('invory.per_page');
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());

        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $path],
        );

        return PreviewRow::collection($paginator->appends(Arr::except($request->query(), self::TRANSIENT_QUERY)));
    }
}
