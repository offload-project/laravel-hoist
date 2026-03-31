<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Tests\Fixtures\Features;

use OffloadProject\Hoist\Attributes\Description;
use OffloadProject\Hoist\Attributes\FeatureSet;
use OffloadProject\Hoist\Attributes\Label;
use OffloadProject\Hoist\Attributes\Tags;
use OffloadProject\Hoist\Contracts\Feature;

#[Label('Attribute Label')]
#[Description('Attribute Description')]
#[Tags('attr-tag')]
#[FeatureSet('attr-set')]
final class MixedFeature implements Feature
{
    public string $name = 'mixed-feature';

    public string $label = 'Property Label';

    public string $description = 'Property Description';

    public array $tags = ['prop-tag'];

    public string $featureSet = 'prop-set';

    public function resolve(mixed $scope): mixed
    {
        return true;
    }

    public function metadata(): array
    {
        return [];
    }
}
