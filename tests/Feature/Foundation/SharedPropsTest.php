<?php

use App\Models\User;
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

it('shares the configured currency and the app locale on authenticated pages', function () {
    actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('app.currency', config('invory.currency'))
            ->where('app.locale', app()->getLocale())
            ->has('app', 2));
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
