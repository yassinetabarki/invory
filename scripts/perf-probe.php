<?php

declare(strict_types=1);

/*
 * Performance probe (phase 3 plan, appendix; ADR-0007).
 *
 * Boots the app and handles each given GET URI twice in this process, as an existing verified user:
 *   cold = the first request (classes and files are loaded, so only the first URI of a run is a true cold start)
 *   warm = the second request (the app's own work)
 * It reports the status, the payload size, the number of queries and the number of PHP files included.
 *
 * Read-only: the session is the `array` driver and nothing is written to the database. The user is the first verified one.
 * It runs natively (the PHP on your PATH) from the project root or any working directory:
 *
 *   php scripts/perf-probe.php /products/create
 *   php scripts/perf-probe.php /dashboard /materials /products json q
 *
 * Natively the database host comes from .env (DB_HOST=127.0.0.1); override it for one run with an environment variable
 * (PowerShell: `$env:DB_HOST='127.0.0.1'; php scripts/perf-probe.php /dashboard`).
 */

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

const USAGE = <<<'TXT'
Usage: php scripts/perf-probe.php <uri> [<uri> ...] [json|html] [q|props]

  <uri>    a GET path of the app, e.g. /dashboard or "/materials?search=denim" (one or more, each starting with "/")
  json     (default) send it as an Inertia XHR, so the response is the page's JSON
  html     send it as a first page load (the full HTML document)
  q        also list every SQL query of the warm request, with its time
  props    also list the size of every Inertia prop (json mode only)

Read-only: array session, an existing verified user, no writes. "cold" is meaningful for the first URI only (the files
are loaded by then); put the URI you care about first, or run the script once per URI.

TXT;

$arguments = array_slice($argv, 1);

if (in_array('-h', $arguments, true) || in_array('--help', $arguments, true)) {
    echo USAGE;
    exit(0);
}

$uris = array_values(array_filter($arguments, static fn (string $argument): bool => str_starts_with($argument, '/')));
$options = array_values(array_diff($arguments, $uris));
$unknown = array_values(array_diff($options, ['json', 'html', 'q', 'props']));

if ($unknown !== []) {
    fwrite(STDERR, 'Unknown argument: '.implode(', ', $unknown).PHP_EOL.PHP_EOL);
}

if ($uris === [] || $unknown !== []) {
    fwrite(STDERR, USAGE);
    exit(1);
}

$mode = in_array('html', $options, true) ? 'html' : 'json';
$extra = in_array('q', $options, true) ? 'q' : (in_array('props', $options, true) ? 'props' : '');
$headers = $mode === 'json' ? ['HTTP_X_INERTIA' => 'true', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : [];

$base = dirname(__DIR__);

require $base.'/vendor/autoload.php';

$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$app->instance('request', Request::create($uris[0], 'GET', [], [], [], $headers));
$kernel->bootstrap();
$app['config']->set('session.driver', 'array');

try {
    $user = User::query()->whereNotNull('email_verified_at')->first();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Could not read the users table: '.$exception->getMessage().PHP_EOL);
    fwrite(STDERR, 'Is PostgreSQL up (docker compose up -d) and DB_HOST set to 127.0.0.1 for a native run?'.PHP_EOL);
    exit(1);
}

if (! $user instanceof User) {
    fwrite(STDERR, 'No verified user in the database: register or seed one (email_verified_at must be set), then run again.'.PHP_EOL);
    exit(1);
}

$app['auth']->guard('web')->setUser($user);

$version = (string) $app->make(HandleInertiaRequests::class)->version($app['request']);
$make = static fn (string $uri): Request => Request::create(
    $uri,
    'GET',
    [],
    [],
    [],
    $headers === [] ? [] : $headers + ['HTTP_X_INERTIA_VERSION' => $version],
);

$queries = [];
$app['db']->listen(function ($query) use (&$queries): void {
    $queries[] = round($query->time, 2).'ms '.$query->sql;
});

foreach ($uris as $uri) {
    $started = microtime(true);
    $kernel->handle($make($uri));
    $cold = (microtime(true) - $started) * 1000;

    $queries = [];
    $request = $make($uri);
    $app->instance('request', $request);
    $started = microtime(true);
    $response = $kernel->handle($request);
    $warm = (microtime(true) - $started) * 1000;

    printf(
        "%-26s %d cold=%.0fms warm=%.0fms bytes=%d queries=%d files=%d\n",
        $uri,
        $response->getStatusCode(),
        $cold,
        $warm,
        strlen((string) $response->getContent()),
        count($queries),
        count(get_included_files()),
    );

    if ($extra === 'q') {
        foreach ($queries as $query) {
            echo "   $query\n";
        }
    }

    if ($extra === 'props') {
        $page = json_decode((string) $response->getContent(), true);

        foreach (is_array($page) ? ($page['props'] ?? []) : [] as $name => $value) {
            printf("   %-20s %6d B\n", $name, strlen((string) json_encode($value)));
        }
    }
}

if (count($uris) > 1) {
    echo "(cold is a true cold start for the first URI only)\n";
}
