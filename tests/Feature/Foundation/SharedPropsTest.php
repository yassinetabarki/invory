<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(fn () => $this->user = User::factory()->create());

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

it('keeps the starter kit shared props', function () {
    actingAs($this->user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', config('app.name'))
            ->where('auth.user.id', $this->user->id)
            ->has('sidebarOpen'));
});
