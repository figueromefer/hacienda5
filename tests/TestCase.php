<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $environment = $app->environment();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($environment !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Unsafe test database configuration: expected APP_ENV=testing, DB_CONNECTION=sqlite and DB_DATABASE=:memory:; got APP_ENV=%s, DB_CONNECTION=%s and DB_DATABASE=%s.',
                var_export($environment, true),
                var_export($connection, true),
                var_export($database, true),
            ));
        }

        return $app;
    }
}
