<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests;

use OffloadProject\Hoist\HoistServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure test feature directories
        config()->set('hoist.feature_directories', [
            __DIR__ . '/Fixtures/Features' => 'OffloadProject\\Hoist\\Tests\\Fixtures\\Features',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            HoistServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
