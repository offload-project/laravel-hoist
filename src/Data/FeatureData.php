<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Data;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelData\Data;

final class FeatureData extends Data
{
    public function __construct(
        public string $name,
        public string $label,
        public ?string $description,
        public ?string $href,
        public ?bool $active = null,
        public array $metadata = [],
    ) {}

    public static function fromClass(object $feature, ?bool $active = null): self
    {
        $route = $feature->route ?? null;

        return new self(
            name: $feature->name ?? class_basename($feature::class),
            label: $feature->label,
            description: $feature->description ?? null,
            href: $route && Route::has($route) ? route($route) : null,
            active: $active,
            metadata: method_exists($feature, 'metadata') ? $feature->metadata() : [],
        );
    }
}
