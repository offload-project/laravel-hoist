<?php

declare(strict_types=1);

namespace OffloadProject\Hoist\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Pennant\Feature;
use OffloadProject\Hoist\Contracts\Feature as FeatureContract;
use OffloadProject\Hoist\Data\FeatureData;
use ReflectionClass;
use ReflectionException;

final class FeatureDiscovery
{
    private array $directoryMap = [];

    private ?Collection $discoveredFeatures = null;

    public function __construct()
    {
        $this->directoryMap = config('hoist.feature_directories', []);
    }

    /**
     * Discover all feature classes from configured directories.
     */
    public function discover(): Collection
    {
        if ($this->discoveredFeatures !== null) {
            return $this->discoveredFeatures;
        }

        $features = collect();

        foreach ($this->directoryMap as $directory => $namespace) {
            $features = $features->merge($this->discoverFromDirectory($directory, $namespace));
        }

        return $this->discoveredFeatures = $features;
    }

    /**
     * Get all features as FeatureData objects.
     */
    public function all(): Collection
    {
        return $this->discover()->map(fn (string $featureClass) => FeatureData::fromClass(app($featureClass)));
    }

    /**
     * Get all features for a specific model (e.g., user) with active status.
     */
    public function forModel(mixed $model): Collection
    {
        return $this->discover()->map(function (string $featureClass) use ($model) {
            $feature = app($featureClass);

            return FeatureData::fromClass(
                $feature,
                Feature::for($model)->active($this->getFeatureName($feature))
            );
        });
    }

    /**
     * Get an array of all feature names.
     */
    public function names(): array
    {
        return $this->discover()
            ->map(fn (string $featureClass) => $this->getFeatureName(app($featureClass)))
            ->values()
            ->toArray();
    }

    /**
     * Get features with a specific tag.
     */
    public function tagged(string $tag): Collection
    {
        return $this->all()->filter(fn (FeatureData $feature) => in_array($tag, $feature->tags, true));
    }

    /**
     * Get features with ALL specified tags (AND logic).
     */
    public function withTags(array $tags): Collection
    {
        return $this->all()->filter(function (FeatureData $feature) use ($tags) {
            foreach ($tags as $tag) {
                if (! in_array($tag, $feature->tags, true)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get features with ANY of the specified tags (OR logic).
     */
    public function withAnyTags(array $tags): Collection
    {
        return $this->all()->filter(function (FeatureData $feature) use ($tags) {
            foreach ($tags as $tag) {
                if (in_array($tag, $feature->tags, true)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get features with a specific tag for a model (includes active status).
     */
    public function taggedFor(string $tag, mixed $model): Collection
    {
        return $this->forModel($model)->filter(fn (FeatureData $feature) => in_array($tag, $feature->tags, true));
    }

    /**
     * Get features with ALL specified tags for a model (includes active status).
     */
    public function withTagsFor(array $tags, mixed $model): Collection
    {
        return $this->forModel($model)->filter(function (FeatureData $feature) use ($tags) {
            foreach ($tags as $tag) {
                if (! in_array($tag, $feature->tags, true)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get features with ANY of the specified tags for a model (includes active status).
     */
    public function withAnyTagsFor(array $tags, mixed $model): Collection
    {
        return $this->forModel($model)->filter(function (FeatureData $feature) use ($tags) {
            foreach ($tags as $tag) {
                if (in_array($tag, $feature->tags, true)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get the feature name from a feature instance.
     */
    private function getFeatureName(object $feature): string
    {
        return $feature->name ?? class_basename($feature::class);
    }

    /**
     * Discover feature classes from a specific directory.
     */
    private function discoverFromDirectory(string $directory, string $namespace): Collection
    {
        if (! File::isDirectory($directory)) {
            return collect();
        }

        $features = collect();

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->getClassFromFile($file->getPathname(), $directory, $namespace);

            if ($class && $this->isFeatureClass($class)) {
                $features->push($class);
            }
        }

        return $features;
    }

    /**
     * Extract the fully qualified class name from a file.
     */
    private function getClassFromFile(string $filePath, string $baseDirectory, string $namespace): ?string
    {
        if (! $namespace) {
            return null;
        }

        $relativePath = str_replace($baseDirectory, '', $filePath);
        $relativePath = mb_trim($relativePath, DIRECTORY_SEPARATOR);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        $class = $namespace.'\\'.$relativePath;

        return class_exists($class) ? $class : null;
    }

    /**
     * Determine if a class is a valid feature class.
     */
    private function isFeatureClass(string $class): bool
    {
        try {
            $reflection = new ReflectionClass($class);

            // Skip abstract classes and interfaces
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return false;
            }

            // Check if it implements the Feature contract or has a resolve method (Pennant compatibility)
            return $reflection->implementsInterface(FeatureContract::class)
                || $reflection->hasMethod('resolve');
        } catch (ReflectionException $e) {
            return false;
        }
    }
}
