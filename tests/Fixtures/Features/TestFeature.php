<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests\Fixtures\Features;

final class TestFeature
{
    public string $name = 'test-feature';

    public string $label = 'Test Feature';

    public string $description = 'A test feature';

    public function resolve($user): bool
    {
        return true;
    }

    public function metadata(): array
    {
        return [
            'category' => 'testing',
            'version' => '1.0',
        ];
    }
}
