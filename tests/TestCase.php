<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuses to run on a PostgreSQL database other than `testing` (the dev database would be wiped by RefreshDatabase).
     * The check runs when the app boots, before any trait (RefreshDatabase included) touches the database.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = (string) $app['config']->get('database.default');

        if ($app['config']->get("database.connections.{$connection}.driver") === 'pgsql') {
            $database = (string) $app['config']->get("database.connections.{$connection}.database");

            if ($database !== 'testing') {
                throw new RuntimeException("PostgreSQL tests must run on the \"testing\" database, not \"{$database}\". Use composer test:pgsql.");
            }
        }

        return $app;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
