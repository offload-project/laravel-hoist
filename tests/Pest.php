<?php

declare(strict_types=1);

use OffloadProject\Hoist\HoistServiceProvider;
use Orchestra\Testbench\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        // Configure test feature directories
        config()->set('hoist.feature_directories', [
            __DIR__.'/Fixtures/Features' => 'OffloadProject\\Hoist\\Tests\\Fixtures\\Features',
        ]);
    })
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function getPackageProviders($app)
{
    return [
        HoistServiceProvider::class,
    ];
}
