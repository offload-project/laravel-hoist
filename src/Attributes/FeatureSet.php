<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FeatureSet
{
    public function __construct(
        public string $name,
        public ?string $label = null,
    ) {}
}
