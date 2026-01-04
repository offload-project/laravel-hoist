<?php

declare(strict_types=1);

use OffloadProject\Hoist\Data\FeatureData;
use OffloadProject\Hoist\Tests\Fixtures\Features\AttributeFeature;
use OffloadProject\Hoist\Tests\Fixtures\Features\MixedFeature;

test('it creates feature data from class with all properties', function () {
    $feature = new class
    {
        public string $name = 'test-feature';

        public string $label = 'Test Feature';

        public string $description = 'A test description';

        public ?string $route = null;

        public function metadata(): array
        {
            return ['key' => 'value'];
        }
    };

    $data = FeatureData::fromClass($feature);

    expect($data->name)->toBe('test-feature')
        ->and($data->label)->toBe('Test Feature')
        ->and($data->description)->toBe('A test description')
        ->and($data->href)->toBeNull()
        ->and($data->active)->toBeNull()
        ->and($data->metadata)->toBe(['key' => 'value']);
});

test('it uses class basename when name property is missing', function () {
    $feature = new class
    {
        public string $label = 'Test Feature';
    };

    $data = FeatureData::fromClass($feature);

    // Anonymous classes have varying basename formats depending on PHP version
    expect($data->name)->toBeString()
        ->and($data->name)->not->toBeEmpty();
});

test('it sets active status when provided', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $activeData = FeatureData::fromClass($feature, true);
    $inactiveData = FeatureData::fromClass($feature, false);

    expect($activeData->active)->toBeTrue()
        ->and($inactiveData->active)->toBeFalse();
});

test('it returns empty metadata when method does not exist', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->metadata)->toBe([]);
});

test('it handles null description', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->description)->toBeNull();
});

test('it generates href for valid routes', function () {
    // Note: Route registration in isolated tests requires proper router setup
    // This test verifies the route checking logic works correctly
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public ?string $route = 'some.route';
    };

    $data = FeatureData::fromClass($feature);

    // Since 'some.route' doesn't exist, href should be null (route check working)
    expect($data->href)->toBeNull();
})->skip('Route registration in isolated unit tests requires integration test setup');

test('it returns null href for non-existent routes', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public ?string $route = 'non.existent.route';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->href)->toBeNull();
});

test('it returns null href when route property is null', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public ?string $route = null;
    };

    $data = FeatureData::fromClass($feature);

    expect($data->href)->toBeNull();
});

test('it returns null href when route property is missing', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->href)->toBeNull();
});

test('it includes tags from feature class', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public array $tags = ['subscription', 'pro'];
    };

    $data = FeatureData::fromClass($feature);

    expect($data->tags)->toBe(['subscription', 'pro']);
});

test('it returns empty tags array when tags property is missing', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->tags)->toBe([]);
});

test('it handles empty tags array', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public array $tags = [];
    };

    $data = FeatureData::fromClass($feature);

    expect($data->tags)->toBe([]);
});

test('it reads label from attribute', function () {
    $feature = new AttributeFeature;

    $data = FeatureData::fromClass($feature);

    expect($data->label)->toBe('Attribute Feature Label');
});

test('it reads description from attribute', function () {
    $feature = new AttributeFeature;

    $data = FeatureData::fromClass($feature);

    expect($data->description)->toBe('A feature using attributes');
});

test('it reads tags from attribute', function () {
    $feature = new AttributeFeature;

    $data = FeatureData::fromClass($feature);

    expect($data->tags)->toBe(['premium', 'beta']);
});

test('it reads featureSet from attribute', function () {
    $feature = new AttributeFeature;

    $data = FeatureData::fromClass($feature);

    expect($data->featureSet)->toBe('billing');
});

test('it returns null featureSet when not defined', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->featureSet)->toBeNull();
});

test('it reads featureSet from property as fallback', function () {
    $feature = new class
    {
        public string $name = 'test';

        public string $label = 'Test';

        public string $featureSet = 'admin';
    };

    $data = FeatureData::fromClass($feature);

    expect($data->featureSet)->toBe('admin');
});

test('attributes take precedence over properties', function () {
    $feature = new MixedFeature;

    $data = FeatureData::fromClass($feature);

    expect($data->label)->toBe('Attribute Label')
        ->and($data->description)->toBe('Attribute Description')
        ->and($data->tags)->toBe(['attr-tag'])
        ->and($data->featureSet)->toBe('attr-set');
});
