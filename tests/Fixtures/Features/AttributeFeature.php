<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests\Fixtures\Features;

use OffloadProject\Hoist\Attributes\Description;
use OffloadProject\Hoist\Attributes\FeatureSet;
use OffloadProject\Hoist\Attributes\Label;
use OffloadProject\Hoist\Attributes\Tags;
use OffloadProject\Hoist\Contracts\Feature;

#[Label('Attribute Feature Label')]
#[Description('A feature using attributes')]
#[Tags('premium', 'beta')]
#[FeatureSet('billing')]
final class AttributeFeature implements Feature
{
    public string $name = 'attribute-feature';

    public function resolve(mixed $scope): mixed
    {
        return true;
    }

    public function metadata(): array
    {
        return [];
    }
}
