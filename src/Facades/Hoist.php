<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use OffloadProject\Hoist\Services\FeatureDiscovery;

/**
 * @method static Collection discover()
 * @method static Collection all()
 * @method static Collection forModel(mixed $model)
 * @method static array names()
 *
 * @see FeatureDiscovery
 */
final class Hoist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeatureDiscovery::class;
    }
}
