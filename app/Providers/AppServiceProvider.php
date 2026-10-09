<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Abilities that are not tied to a model policy.
     */
    protected function configureAuthorization(): void
    {
        // MOCK (ADR-0006, removed by S-17): preview-only controllers (sales, dashboard, restocks) serve fixture data and
        // must never be reachable in production. Products are real since S-04b and are guarded by ProductPolicy.
        Gate::define('access-preview', fn (User $user): bool => ! app()->isProduction());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Lazy loading (N+1), silently discarded and missing attributes throw outside production.
        Model::shouldBeStrict(! app()->isProduction());

        // Single resources are passed flat (`material: {...}`), see the Phase 1 contract.
        JsonResource::withoutWrapping();

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
