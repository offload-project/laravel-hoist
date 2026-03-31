<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Data;

use Illuminate\Support\Facades\Route;
use OffloadProject\Hoist\Attributes\Description;
use OffloadProject\Hoist\Attributes\FeatureSet;
use OffloadProject\Hoist\Attributes\Label;
use OffloadProject\Hoist\Attributes\Route as RouteAttribute;
use OffloadProject\Hoist\Attributes\Tags;
use ReflectionClass;
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
        public array $tags = [],
        public ?string $featureSet = null,
    ) {}

    public static function fromClass(object $feature, ?bool $active = null): self
    {
        $reflection = new ReflectionClass($feature);

        $labelAttr = self::getAttribute($reflection, Label::class);
        $label = $labelAttr !== null ? $labelAttr->value
            : (property_exists($feature, 'label') ? $feature->label : null)
            ?? class_basename($feature::class);

        $descriptionAttr = self::getAttribute($reflection, Description::class);
        $description = $descriptionAttr !== null ? $descriptionAttr->value
            : (property_exists($feature, 'description') ? $feature->description : null);

        $routeAttr = self::getAttribute($reflection, RouteAttribute::class);
        $route = $routeAttr !== null ? $routeAttr->value
            : (property_exists($feature, 'route') ? $feature->route : null);

        $tagsAttr = self::getAttribute($reflection, Tags::class);
        $tags = $tagsAttr !== null ? $tagsAttr->value
            : (property_exists($feature, 'tags') ? $feature->tags : []);

        $featureSetAttr = self::getAttribute($reflection, FeatureSet::class);
        $featureSet = $featureSetAttr !== null ? $featureSetAttr->name
            : (property_exists($feature, 'featureSet') ? $feature->featureSet : null);

        return new self(
            name: property_exists($feature, 'name') ? $feature->name : class_basename($feature::class),
            label: $label,
            description: $description,
            href: $route && Route::has($route) ? route($route) : null,
            active: $active,
            metadata: method_exists($feature, 'metadata') ? $feature->metadata() : [],
            tags: $tags,
            featureSet: $featureSet,
        );
    }

    /**
     * @template T of object
     *
     * @param  ReflectionClass<object>  $reflection
     * @param  class-string<T>  $attributeClass
     * @return T|null
     */
    private static function getAttribute(ReflectionClass $reflection, string $attributeClass): ?object
    {
        $attributes = $reflection->getAttributes($attributeClass);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
