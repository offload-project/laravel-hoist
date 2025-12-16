<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Contracts;

/**
 * Contract for feature flag classes.
 *
 * Feature classes should have the following public properties:
 * - string $name: The feature identifier
 * - string $label: Human-readable label
 * - ?string $description: Optional description
 * - ?string $route: Optional route name for generating href
 *
 * And optionally a metadata() method returning an array.
 */
interface Feature
{
    /**
     * Resolve the feature's initial value for the given scope.
     */
    public function resolve(mixed $scope): mixed;
}
