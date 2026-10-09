<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Reference\ReferenceData;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            ...$this->reference($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->authUser($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'app' => [
                'currency' => config('invory.currency'),
                'locale' => app()->getLocale(),
                // 'YYYY-MM-DD' in the app timezone: default and max for date inputs.
                'today' => now()->toDateString(),
            ],
        ];
    }

    /**
     * The managed reference lists (units, categories) and the fixed enum options, shared with signed-in users only (a guest has
     * no key, so the login visit loads them fresh). A once-prop: its key carries the cache version that the reference-data
     * Actions bump, so a client that already holds the current key sends `X-Inertia-Except-Once-Props` and the server skips the
     * work, and any write makes the next visit send the lists again (ADR-0008, ADR-0009).
     *
     * @return array<string, mixed>
     */
    private function reference(Request $request): array
    {
        if (! $request->user() instanceof User) {
            return [];
        }

        return [
            'reference' => Inertia::once(fn (): array => app(ReferenceData::class)->all())
                ->as(app(ReferenceVersion::class)->propKey()),
        ];
    }

    /**
     * The signed-in user as the `User` type of resources/js/types/auth.ts: an explicit allow-list, never the whole model, so a
     * future column (or a secret such as the password hash, the remember token or the two-factor secret) can't reach the page.
     * Dates keep the model's own serialisation. Null for guests.
     *
     * @return array<string, mixed>|null
     */
    private function authUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return Arr::only($user->attributesToArray(), [
            'id',
            'name',
            'email',
            'email_verified_at',
            'created_at',
            'updated_at',
        ]);
    }
}
