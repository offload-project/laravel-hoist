<p align="center">
    <a href="https://packagist.org/packages/offload-project/laravel-hoist"><img src="https://img.shields.io/packagist/v/offload-project/laravel-hoist.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/offload-project/laravel-hoist/actions"><img src="https://img.shields.io/github/actions/workflow/status/offload-project/laravel-hoist/tests.yml?branch=main&style=flat-square" alt="GitHub Tests Action Status"></a>
    <a href="https://packagist.org/packages/offload-project/laravel-hoist"><img src="https://img.shields.io/packagist/dt/offload-project/laravel-hoist.svg?style=flat-square" alt="Total Downloads"></a>
</p>

# Laravel Hoist

Feature discovery and management extension for Laravel Pennant. Automatically discover, manage, and serve feature flags
with custom metadata and routing.

## Requirements

- PHP 8.3+
- Laravel 11+
- Laravel Pennant 1+

## Installation

```bash
composer require offload-project/laravel-hoist
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=hoist-config
```

Edit `config/hoist.php`:

```php
return [
    'feature_directories' => [
        app_path('Features') => 'App\\Features',
    ],
];
```

The configuration uses an associative array where keys are directory paths and values are their corresponding
namespaces.

Optionally, publish the stub files for customization:

```bash
php artisan vendor:publish --tag=hoist-stubs
```

## Features

### Feature Discovery

Automatically discover and manage Laravel Pennant features with custom metadata and routing information.

#### Create a Feature

```bash
php artisan hoist:feature NewFeature
```

This will create a new feature class in your configured feature directory (default: `app/Features`).

#### Feature Class Example

```php
<?php

declare(strict_types=1);

namespace App\Features;

use OffloadProject\Hoist\Contracts\Feature;

class BillingFeature implements Feature
{
    public string $name = 'billing';
    public string $label = 'Billing Module';
    public ?string $description = 'Advanced billing features';
    public ?string $route = 'billing.index'; // Optional route name
    public array $tags = ['subscription', 'pro']; // Feature tags

    public function resolve(mixed $scope): mixed
    {
        return $scope->subscription?->isActive() ?? false;
    }

    public function metadata(): array
    {
        return [
            'category' => 'premium',
            'icon' => 'credit-card',
            'version' => '2.0',
        ];
    }
}
```

> **Note:** The `Feature` interface is optional but recommended. Features are discovered based on having a `resolve()` method, but implementing the interface provides better IDE support and type safety.

#### Using Features

```php
use OffloadProject\Hoist\Facades\Hoist;

// Get all features
$features = Hoist::all();

// Get features for a specific user with active status
$userFeatures = Hoist::forModel($user);

// Get array of all feature names
$featureNames = Hoist::names();
// Returns: ['billing', 'dashboard', 'reporting', ...]

// Access feature data
foreach ($userFeatures as $feature) {
    echo $feature->name;        // 'billing'
    echo $feature->label;       // 'Billing Module'
    echo $feature->description; // 'Advanced billing features'
    echo $feature->href;        // route('billing.index')
    echo $feature->active;      // true/false
    print_r($feature->metadata); // ['category' => 'premium', ...]
    print_r($feature->tags);     // ['subscription', 'pro']
}
```

### Feature Discovery Service

The `FeatureDiscovery` service provides several methods for working with features:

#### discover()

Discovers all feature classes from configured directories.

```php
use OffloadProject\Hoist\Services\FeatureDiscovery;

$discovery = app(FeatureDiscovery::class);
$featureClasses = $discovery->discover();
// Returns: Collection of feature class names
```

#### all()

Returns all features as `FeatureData` objects without checking active status.

```php
$features = Hoist::all();
// Returns: Collection of FeatureData objects
```

#### forModel($model)

Returns all features with their active status for a specific model (typically a User).

```php
$userFeatures = Hoist::forModel($user);
// Each FeatureData object includes 'active' property
```

#### names()

Returns an array of all feature names.

```php
$names = Hoist::names();
// Returns: ['feature-one', 'feature-two', ...]
```

### Feature Tags

Tags provide a flexible way to categorize features for filtering. Use tags to separate feature flags from subscription features, or to group features by plan tier.

#### Define Tags

```php
class DarkMode implements Feature
{
    public string $name = 'dark-mode';
    public string $label = 'Dark Mode';
    public array $tags = ['flag', 'ui'];
    // ...
}

class AdvancedReporting implements Feature
{
    public string $name = 'advanced-reporting';
    public string $label = 'Advanced Reporting';
    public array $tags = ['subscription', 'pro', 'enterprise'];
    // ...
}
```

#### Filter by Tags

```php
// Get features with a specific tag
$flags = Hoist::tagged('flag');
$subscriptionFeatures = Hoist::tagged('subscription');

// Get features with ALL specified tags (AND logic)
$proSubscriptions = Hoist::withTags(['subscription', 'pro']);

// Get features with ANY of the specified tags (OR logic)
$paidFeatures = Hoist::withAnyTags(['pro', 'enterprise']);
```

#### Filter with Model Scope

Include active status when filtering by tags:

```php
// Get subscription features for a user with active status
$features = Hoist::taggedFor('subscription', $user);

// Get pro features for a user
$proFeatures = Hoist::withTagsFor(['subscription', 'pro'], $user);

// Get any paid tier features for a user
$paidFeatures = Hoist::withAnyTagsFor(['pro', 'enterprise'], $user);
```

### Feature Data Structure

The `FeatureData` class provides a structured way to access feature information:

```php
class FeatureData
{
    public string $name;        // Feature identifier
    public string $label;       // Human-readable name
    public ?string $description; // Feature description
    public ?string $href;       // Generated route URL
    public ?bool $active;       // Active status (when using forModel)
    public array $metadata;     // Custom metadata
    public array $tags;         // Feature tags for categorization
}
```

## Integration with Laravel Pennant

This package extends Laravel Pennant by providing:

1. **Automatic Discovery**: No need to manually register features
2. **Rich Metadata**: Add custom metadata to features
3. **Route Integration**: Link features to routes automatically
4. **Structured Data**: Get features as structured data objects
5. **Bulk Operations**: Get all features and their status in one call

### Using with Pennant's Native Features

You can still use all of Laravel Pennant's native features:

```php
use Laravel\Pennant\Feature;

// Standard Pennant usage
if (Feature::active('billing')) {
    // Feature is active
}

// In Blade
@feature('billing')
    <!-- Feature content -->
@endfeature

// Combined with Pennant Hoist
$features = Hoist::forModel($user);
foreach ($features as $feature) {
    if ($feature->active) {
        // Do something with active feature
    }
}
```

## Use Cases

### Building a Feature Dashboard

```php
public function featureDashboard(Request $request)
{
    $features = Hoist::forModel($request->user());

    return view('features.dashboard', [
        'features' => $features,
    ]);
}
```

```blade
<!-- resources/views/features/dashboard.blade.php -->
<div class="features-grid">
    @foreach($features as $feature)
        <div class="feature-card {{ $feature->active ? 'active' : 'inactive' }}">
            <h3>{{ $feature->label }}</h3>
            <p>{{ $feature->description }}</p>

            @if($feature->active && $feature->href)
                <a href="{{ $feature->href }}" class="btn">
                    Go to {{ $feature->label }}
                </a>
            @endif

            @if(!empty($feature->metadata['icon']))
                <i class="icon-{{ $feature->metadata['icon'] }}"></i>
            @endif
        </div>
    @endforeach
</div>
```

### API Endpoint for Frontend

```php
Route::get('/api/features', function (Request $request) {
    return Hoist::forModel($request->user());
});
```

Returns:

```json
[
  {
    "name": "billing",
    "label": "Billing Module",
    "description": "Advanced billing features",
    "href": "https://app.example.com/billing",
    "active": true,
    "metadata": {
      "category": "premium",
      "icon": "credit-card"
    },
    "tags": ["subscription", "pro"]
  }
]
```

### Dynamic Navigation

```php
public function navigation(Request $request)
{
    $features = Hoist::forModel($request->user())
        ->filter(fn($f) => $f->active && $f->href)
        ->filter(fn($f) => $f->metadata['show_in_nav'] ?? true);

    return view('layouts.navigation', [
        'features' => $features,
    ]);
}
```

## Advanced Usage

### Custom Feature Directories

You can configure multiple feature directories, each mapped to its namespace:

```php
// config/hoist.php
return [
    'feature_directories' => [
        app_path('Authorization/Features') => 'App\\Authorization\\Features',
        app_path('Billing/Features') => 'App\\Billing\\Features',
        app_path('Admin/Features') => 'App\\Admin\\Features',
    ],
];
```

Each directory is mapped to its corresponding namespace, allowing you to organize features across different modules or
domains while maintaining proper class resolution.

### Feature Organization

Organize features by category:

```
app/Features/
├── Admin/
│   ├── UserManagementFeature.php
│   └── SystemSettingsFeature.php
├── Premium/
│   ├── BillingFeature.php
│   └── AnalyticsFeature.php
└── Core/
    ├── DashboardFeature.php
    └── ProfileFeature.php
```

### Route Handling

The `href` property in `FeatureData` is generated from the feature's `$route` property. The package safely handles routes:

- If `$route` is `null` or empty, `href` will be `null`
- If `$route` specifies a route name that doesn't exist, `href` will be `null` (no exception thrown)
- If `$route` specifies a valid route name, `href` will contain the generated URL

```php
class MyFeature implements Feature
{
    public string $name = 'my-feature';
    public string $label = 'My Feature';
    public ?string $route = 'dashboard.index'; // Must be a valid route name

    // ...
}
```

### The Feature Interface

The package provides an optional `Feature` interface for better type safety:

```php
use OffloadProject\Hoist\Contracts\Feature;

class MyFeature implements Feature
{
    public string $name = 'my-feature';
    public string $label = 'My Feature';
    public ?string $description = null;
    public ?string $route = null;
    public array $tags = [];

    public function resolve(mixed $scope): mixed
    {
        return true;
    }

    public function metadata(): array
    {
        return [];
    }
}
```

Features are discovered if they either:
1. Implement the `Feature` interface, OR
2. Have a `resolve()` method (for backward compatibility with plain Pennant features)

### Metadata Best Practices

Use metadata for:

- **Categorization**: Group features by category
- **UI Elements**: Icons, colors, badges
- **Permissions**: Access levels, roles
- **Versioning**: Track feature versions
- **Analytics**: Track feature usage

```php
public function metadata(): array
{
    return [
        'category' => 'premium',
        'icon' => 'credit-card',
        'color' => 'blue',
        'version' => '2.0',
        'requires_subscription' => true,
        'min_plan' => 'pro',
    ];
}
```

## Testing

```bash
./vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
