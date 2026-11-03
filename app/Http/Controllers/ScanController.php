<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\User;
use App\Services\Production\ScanLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Scan page (INV-15-c, AC-3 to AC-6, ADR-0024): "Your scans today", and, when opened from a ticket's QR link (`/scan/t/<code>`),
 * that ticket's confirm or the reason nothing can move. The link sits behind `auth`, so a guest signs in and comes straight back.
 */
final class ScanController extends Controller
{
    /** The most scans "Your scans today" lists. */
    public const TODAY_LIMIT = 50;

    public function index(Request $request): Response
    {
        return $this->page($request, null);
    }

    public function show(Request $request, string $code, ScanLookup $lookup): Response
    {
        return $this->page($request, $lookup->byCode($code));
    }

    /** @param  array{confirm: array<string, mixed>|null, refusal: string|null}|null  $found */
    private function page(Request $request, ?array $found): Response
    {
        Gate::authorize('viewAny', ProductionBatch::class);
        $user = $request->user();

        return Inertia::render('scan/index', [
            'scans_today' => $user instanceof User ? self::scansToday($user) : [],
            'confirm' => $found['confirm'] ?? null,
            'refusal' => $found['refusal'] ?? null,
        ]);
    }

    /** @return list<array{number: string, product: string, to: string, at: string}> this member's scans since today 00:00, newest first */
    private static function scansToday(User $user): array
    {
        return array_values(ProductionBatchMove::query()
            ->where('scanned', true)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', today())
            ->with('batch.product')
            ->latest('id')
            ->limit(self::TODAY_LIMIT)
            ->get()
            ->map(static fn (ProductionBatchMove $move): array => [
                'number' => $move->batch->label,
                'product' => $move->batch->product->name,
                'to' => (string) $move->to_label,
                'at' => $move->created_at->toIso8601String(),
            ])
            ->all());
    }
}
