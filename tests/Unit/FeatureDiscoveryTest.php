<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use OffloadProject\Hoist\Contracts\Feature;
use OffloadProject\Hoist\Data\FeatureData;
use OffloadProject\Hoist\Services\FeatureDiscovery;
use OffloadProject\Hoist\Tests\Fixtures\Features\InterfaceFeature;

test('it discovers features from configured directories', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->discover();

    expect($features)->toBeInstanceOf(Collection::class)
        ->and($features)->not->toBeEmpty()
        ->and($features->count())->toBeGreaterThanOrEqual(3);
});

test('it returns feature data objects with all method', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->all();

    expect($features)->toBeInstanceOf(Collection::class)
        ->and($features->first())->toBeInstanceOf(FeatureData::class);
});

test('feature data contains correct information', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->all();
    $testFeature = $features->first(fn ($f) => $f->name === 'test-feature');

    expect($testFeature)->not->toBeNull()
        ->and($testFeature->name)->toBe('test-feature')
        ->and($testFeature->description)->toBe('A test feature')
        ->and($testFeature->metadata)->toBeArray()
        ->and($testFeature->metadata)->toHaveKey('category')
        ->and($testFeature->metadata['category'])->toBe('testing');
});

test('it returns features with active status for a model', function () {
    $user = new class
    {
        public $id = 1;
    };

    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->forModel($user);

    expect($features)->toBeInstanceOf(Collection::class)
        ->and($features->first())->toBeInstanceOf(FeatureData::class)
        ->and($features->first()->active)->not->toBeNull();
})->skip('Requires Pennant FeatureScopeSerializeable implementation on model');

test('it handles empty directories gracefully', function () {
    config()->set('hoist.feature_directories', [__DIR__.'/NonExistent' => 'Test\\NonExistent']);

    $discovery = new FeatureDiscovery();
    $features = $discovery->discover();

    expect($features)->toBeInstanceOf(Collection::class)
        ->and($features)->toBeEmpty();
});

test('feature data has all required properties', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->all();
    $testFeature = $features->first();

    expect($testFeature->name)->toBeString()
        ->and($testFeature->label)->toBeString()
        ->and(property_exists($testFeature, 'description'))->toBeTrue()
        ->and(property_exists($testFeature, 'active'))->toBeTrue()
        ->and(property_exists($testFeature, 'metadata'))->toBeTrue();
});

test('it returns array of all feature names', function () {
    $discovery = app(FeatureDiscovery::class);
    $names = $discovery->names();

    expect($names)->toBeArray()
        ->and($names)->toContain('test-feature')
        ->and($names)->toContain('another-feature')
        ->and($names)->toContain('interface-feature')
        ->and($names)->toHaveCount(3);
});

test('it discovers features implementing the Feature interface', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->discover();

    expect($features)->toContain(InterfaceFeature::class);
});

test('interface feature instance implements Feature contract', function () {
    $feature = new InterfaceFeature();

    expect($feature)->toBeInstanceOf(Feature::class);
});

test('it memoizes discovery results within same instance', function () {
    $discovery = new FeatureDiscovery();

    $first = $discovery->discover();
    $second = $discovery->discover();

    expect($first)->toBe($second);
});

test('it returns same collection reference on repeated discover calls', function () {
    $discovery = app(FeatureDiscovery::class);

    $first = $discovery->discover();
    $second = $discovery->discover();

    // Should be the exact same object reference due to memoization
    expect(spl_object_id($first))->toBe(spl_object_id($second));
});
