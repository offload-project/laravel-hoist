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
        ->and($names)->toContain('attribute-feature')
        ->and($names)->toContain('mixed-feature')
        ->and($names)->toHaveCount(5);
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

test('feature data includes tags property', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->all();
    $testFeature = $features->first(fn ($f) => $f->name === 'test-feature');

    expect($testFeature->tags)->toBeArray()
        ->and($testFeature->tags)->toContain('flag')
        ->and($testFeature->tags)->toContain('testing');
});

test('it filters features by single tag with tagged method', function () {
    $discovery = app(FeatureDiscovery::class);
    $flagFeatures = $discovery->tagged('flag');

    expect($flagFeatures)->toHaveCount(1)
        ->and($flagFeatures->first()->name)->toBe('test-feature');
});

test('it filters subscription features with tagged method', function () {
    $discovery = app(FeatureDiscovery::class);
    $subscriptionFeatures = $discovery->tagged('subscription');

    expect($subscriptionFeatures)->toHaveCount(2);

    $names = $subscriptionFeatures->pluck('name')->toArray();
    expect($names)->toContain('another-feature')
        ->and($names)->toContain('interface-feature');
});

test('it filters features with ALL specified tags using withTags', function () {
    $discovery = app(FeatureDiscovery::class);
    $proSubscriptions = $discovery->withTags(['subscription', 'pro']);

    expect($proSubscriptions)->toHaveCount(1)
        ->and($proSubscriptions->first()->name)->toBe('another-feature');
});

test('it filters features with ANY specified tags using withAnyTags', function () {
    $discovery = app(FeatureDiscovery::class);
    $proOrEnterprise = $discovery->withAnyTags(['pro', 'enterprise']);

    expect($proOrEnterprise)->toHaveCount(2);

    $names = $proOrEnterprise->pluck('name')->toArray();
    expect($names)->toContain('another-feature')
        ->and($names)->toContain('interface-feature');
});

test('it returns empty collection when no features match tag', function () {
    $discovery = app(FeatureDiscovery::class);
    $nonexistent = $discovery->tagged('nonexistent-tag');

    expect($nonexistent)->toBeInstanceOf(Collection::class)
        ->and($nonexistent)->toBeEmpty();
});

test('withTags returns empty when features only partially match', function () {
    $discovery = app(FeatureDiscovery::class);
    // No feature has both 'flag' and 'subscription'
    $result = $discovery->withTags(['flag', 'subscription']);

    expect($result)->toBeEmpty();
});

test('withAnyTags returns all features matching any tag', function () {
    $discovery = app(FeatureDiscovery::class);
    // 'flag' matches test-feature, 'subscription' matches another-feature and interface-feature
    $result = $discovery->withAnyTags(['flag', 'subscription']);

    expect($result)->toHaveCount(3);
});

test('features without tags property return empty tags array', function () {
    $discovery = app(FeatureDiscovery::class);
    $features = $discovery->all();

    foreach ($features as $feature) {
        expect($feature->tags)->toBeArray();
    }
});
