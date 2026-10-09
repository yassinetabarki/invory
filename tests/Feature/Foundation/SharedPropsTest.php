<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Env;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => $this->user = User::factory()->create());

/**
 * The shared `auth.user` prop of a page response.
 *
 * @param  TestResponse<Response>  $response
 * @return array<string, mixed>
 */
function inertiaAuthUser(TestResponse $response): array
{
    $user = $response->inertiaProps('auth.user');
    assert(is_array($user));

    return $user;
}

/**
 * Evaluate config/invory.php against an isolated environment repository, so the
 * result never depends on the developer's .env, the shell or phpunit.xml variables.
 *
 * @param  array<string, string>  $variables
 * @return array<string, mixed>
 */
function invoryConfigWithEnv(array $variables): array
{
    $repository = RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->immutable()
        ->make();

    foreach ($variables as $name => $value) {
        $repository->set($name, $value);
    }

    $property = new ReflectionProperty(Env::class, 'repository');
    $original = $property->getValue();
    $property->setValue(null, $repository);

    try {
        return require config_path('invory.php');
    } finally {
        $property->setValue(null, $original);
    }
}

it('shares the configured currency, the app locale and today on authenticated pages', function () {
    actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('app.currency', config('invory.currency'))
            ->where('app.locale', app()->getLocale())
            ->where('app.today', now()->toDateString())
            ->has('app', 3));
});

it('shares today as a YYYY-MM-DD date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-08 15:42:10'));

    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('app.today', '2026-10-08')
            ->where('app.today', fn (string $today) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $today) === 1));
});

it('shares today in the app timezone, not in UTC', function () {
    $original = date_default_timezone_get();

    try {
        // 20:00 UTC on 8 October is already 9 October in Auckland (UTC+13).
        date_default_timezone_set('Pacific/Auckland');
        $this->travelTo(CarbonImmutable::parse('2026-10-08 20:00:00', 'UTC'));

        actingAs($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('app.today', '2026-10-09'));
    } finally {
        $this->travelBack();
        date_default_timezone_set($original);
    }
});

it('shares today on every authenticated page, not only the dashboard', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-08 09:00:00'));

    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page->where('app.today', '2026-10-08'));
});

it('defaults the currency to TND when APP_CURRENCY is not set', function () {
    expect(invoryConfigWithEnv([])['currency'])->toBe('TND');
});

it('reads the currency from APP_CURRENCY when it is set', function () {
    expect(invoryConfigWithEnv(['APP_CURRENCY' => 'EUR'])['currency'])->toBe('EUR');
});

it('reflects a currency override from config', function () {
    config(['invory.currency' => 'USD']);

    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('app.currency', 'USD'));
});

it('reflects the active locale', function () {
    app()->setLocale('fr');

    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('app.locale', 'fr'));
});

it('shares auth.user as an explicit allow-list, with exactly the keys of the User type', function () {
    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.user', 6)
            ->where('auth.user.id', $this->user->id)
            ->where('auth.user.name', $this->user->name)
            ->where('auth.user.email', $this->user->email)
            ->has('auth.user.email_verified_at')
            ->has('auth.user.created_at')
            ->has('auth.user.updated_at'));

    expect(array_keys(inertiaAuthUser(actingAs($this->user)->get(route('dashboard')))))
        ->toEqualCanonicalizing(['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at']);
});

it('never shares a secret or an internal column of the user', function () {
    $this->user->forceFill([
        'remember_token' => 'remember-me-token',
        'two_factor_secret' => 'two-factor-secret',
        'two_factor_recovery_codes' => 'recovery-codes',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = actingAs($this->user)->get(route('dashboard'));
    $user = inertiaAuthUser($response);

    expect($user)->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'])
        ->and(json_encode($response->inertiaProps()))
        ->not->toContain('remember-me-token')
        ->not->toContain('two-factor-secret')
        ->not->toContain('recovery-codes')
        ->not->toContain($this->user->password);
});

it('serialises the dates of auth.user exactly as the model does', function () {
    $this->user->forceFill(['email_verified_at' => '2026-03-04 05:06:07', 'created_at' => '2026-01-02 03:04:05'])->save();
    $user = inertiaAuthUser(actingAs($this->user)->get(route('dashboard')));
    $model = $this->user->fresh()->toArray();

    expect($user['email_verified_at'])->toBe($model['email_verified_at'])
        ->and($user['created_at'])->toBe($model['created_at'])
        ->and($user['updated_at'])->toBe($model['updated_at'])
        ->and($user['email_verified_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/');
});

it('shares a null email_verified_at for an unverified user', function () {
    $unverified = User::factory()->unverified()->create();

    // The unverified user is redirected to the verification notice, a page that is still an Inertia page with the shared props.
    $user = inertiaAuthUser(actingAs($unverified)->get(route('verification.notice')));

    expect($user['email_verified_at'])->toBeNull()
        ->and($user['id'])->toBe($unverified->id);
});

it('shares auth.user as null for guests', function () {
    get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user', null));
});

it('keeps the starter kit shared props', function () {
    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', config('app.name'))
            ->where('auth.user.id', $this->user->id)
            ->has('sidebarOpen'));
});
