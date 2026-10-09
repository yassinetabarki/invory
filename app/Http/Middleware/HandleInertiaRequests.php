<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
