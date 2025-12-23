<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests\Fixtures\Features;

final class AnotherFeature
{
    public string $name = 'another-feature';

    public string $label = 'Another Feature';

    public array $tags = ['subscription', 'pro'];

    public function resolve($user): bool
    {
        return false;
    }
}
