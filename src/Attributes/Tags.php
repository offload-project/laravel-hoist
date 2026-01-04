<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Tags
{
    public array $value;

    public function __construct(string ...$tags)
    {
        $this->value = $tags;
    }
}
