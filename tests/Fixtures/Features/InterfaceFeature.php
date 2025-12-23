<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests\Fixtures\Features;

use OffloadProject\Hoist\Contracts\Feature;

final class InterfaceFeature implements Feature
{
    public string $name = 'interface-feature';

    public string $label = 'Interface Feature';

    public ?string $description = 'A feature implementing the interface';

    public ?string $route = null;

    public array $tags = ['subscription', 'enterprise'];

    public function resolve(mixed $scope): mixed
    {
        return true;
    }

    public function metadata(): array
    {
        return [
            'implements_interface' => true,
        ];
    }
}
