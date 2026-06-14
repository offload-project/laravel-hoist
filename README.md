# Laravel Hoist

[![Latest Version on Packagist](https://img.shields.io/packagist/v/offload-project/laravel-hoist.svg?style=flat-square)](https://packagist.org/packages/offload-project/laravel-hoist)
[![Tests](https://img.shields.io/github/actions/workflow/status/offload-project/laravel-hoist/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/offload-project/laravel-hoist/actions/workflows/tests.yml)
[![Build](https://img.shields.io/github/actions/workflow/status/offload-project/laravel-hoist/release.yml?label=build&style=flat-square)](https://github.com/offload-project/laravel-hoist/actions/workflows/release.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/offload-project/laravel-hoist.svg?style=flat-square)](https://packagist.org/packages/offload-project/laravel-hoist)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE.md)

Feature discovery and management extension for Laravel Pennant. Automatically discover, manage, and serve feature flags
with custom metadata, tags, and routing.

## Features

- **Automatic discovery** — Drop a class into your `Features` directory; it's picked up without manual registration
- **PHP attributes** — Declarative metadata via `#[Label]`, `#[Description]`, `#[Route]`, `#[Tags]`, `#[FeatureSet]`
- **Rich `FeatureData` payload** — Structured DTO with label, description, href, active status, tags, and metadata
- **Per-user resolution** — `Hoist::forModel($user)` returns every feature with its active status for that scope
- **Tag-based filtering** — Filter features by single tag, ALL tags (AND), or ANY tag (OR)
- **Feature sets** — Group related features under a named set
- **Route integration** — Generate an `href` from a named route, safely handling missing routes
- **Pennant compatible** — Works alongside Pennant's native `Feature::active()`, `@feature` Blade directive, and
  middleware
- **Customizable stubs** — Publish and customize the `hoist:feature` generator template

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
    - [Create a Feature](#1-create-a-feature)
    - [Use Features](#2-use-features)
    - [Filter by Tags](#3-filter-by-tags)
- [Attributes](#attributes)
- [Feature Discovery Service](#feature-discovery-service)
- [Feature Data Structure](#feature-data-structure)
- [Integration with Laravel Pennant](#integration-with-laravel-pennant)
- [Use Cases](#use-cases)
- [Advanced Usage](#advanced-usage)
- [AI Coding Assistant Skill](#ai-coding-assistant-skill)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

## Requirements

- PHP 8.3+
- Laravel 11/12/13
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

## Quick Start

### 1. Create a Feature

```bash
php artisan hoist:feature BillingFeature
```

This creates a new feature class in your configured feature directory (default: `app/Features`).

Features can define metadata using **PHP attributes** (recommended) or **class properties**. Attributes take precedence
over properties when both are present.

```php
<?php

declare(strict_types=1);

namespace App\Features;

use OffloadProject\Hoist\Attributes\Description;
use OffloadProject\Hoist\Attributes\FeatureSet;
use OffloadProject\Hoist\Attributes\Label;
use OffloadProject\Hoist\Attributes\Route;
use OffloadProject\Hoist\Attributes\Tags;
use OffloadProject\Hoist\Contracts\Feature;

#[Label('Billing Module')]
#[Description('Advanced billing features')]
#[Route('billing.index')]
#[Tags('subscription', 'pro')]
#[FeatureSet('premium')]
class BillingFeature implements Feature
{
    public string $name = 'billing';

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

> **Note:** The `Feature` interface is optional but recommended. Features are discovered based on having a `resolve()`
> method, but implementing the interface provides better IDE support and type safety.

### 2. Use Features

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

### 3. Filter by Tags

```php
// Get features with a specific tag
$flags = Hoist::tagged('flag');
$subscriptionFeatures = Hoist::tagged('subscription');

// Get features with ALL specified tags (AND logic)
$proSubscriptions = Hoist::withTags(['subscription', 'pro']);

// Get features with ANY of the specified tags (OR logic)
$paidFeatures = Hoist::withAnyTags(['pro', 'enterprise']);

// Filter with model scope (includes active status)
$features = Hoist::taggedFor('subscription', $user);
$proFeatures = Hoist::withTagsFor(['subscription', 'pro'], $user);
$paidFeatures = Hoist::withAnyTagsFor(['pro', 'enterprise'], $user);
```

## Attributes

PHP attributes provide a clean, declarative way to define feature metadata directly on the class. All attributes are
optional and target the class level.

| Attribute               | Parameter                      | Description                                     |
|-------------------------|--------------------------------|-------------------------------------------------|
| `#[Label('...')]`       | `string $value`                | Human-readable display name                     |
| `#[Description('...')]` | `string $value`                | Feature description                             |
| `#[Route('...')]`       | `string $value`                | Named route for generating the feature's `href` |
| `#[Tags('...')]`        | `string ...$tags`              | One or more tags for categorization             |
| `#[FeatureSet('...')]`  | `string $name, ?string $label` | Group features into a named set                 |

When an attribute is present, it takes precedence over the equivalent class property. You can mix both approaches — for
example, use attributes for static metadata and properties for values that need to be computed.

```php
// Properties-only approach (still supported)
class MyFeature implements Feature
{
    public string $name = 'my-feature';
    public string $label = 'My Feature';
    public ?string $description = 'A description';
    public ?string $route = 'my-feature.index';
    public array $tags = ['flag'];
    public string $featureSet = 'core';

    public function resolve(mixed $scope): mixed
    {
        return true;
    }
}
```

## Feature Discovery Service

The `FeatureDiscovery` service (accessed via the `Hoist` facade) provides several methods for working with features:

| Method                             | Returns                                           |
|------------------------------------|---------------------------------------------------|
| `Hoist::discover()`                | Collection of discovered feature class names      |
| `Hoist::all()`                     | Collection of `FeatureData` without active status |
| `Hoist::forModel($model)`          | Collection of `FeatureData` with active status    |
| `Hoist::names()`                   | Array of all feature names                        |
| `Hoist::tagged($tag)`              | Features with the given tag                       |
| `Hoist::withTags([...])`           | Features with ALL given tags (AND)                |
| `Hoist::withAnyTags([...])`        | Features with ANY of the given tags (OR)          |
| `Hoist::taggedFor($tag, $m)`       | Tagged features with active status for `$m`       |
| `Hoist::withTagsFor([...], $m)`    | All-tags features with active status for `$m`     |
| `Hoist::withAnyTagsFor([...], $m)` | Any-tags features with active status for `$m`     |

## Feature Data Structure

The `FeatureData` class provides a structured way to access feature information:

```php
class FeatureData
{
    public string $name;         // Feature identifier
    public string $label;        // Human-readable name
    public ?string $description; // Feature description
    public ?string $href;        // Generated route URL
    public ?bool $active;        // Active status (when using forModel)
    public array $metadata;      // Custom metadata
    public array $tags;          // Feature tags for categorization
    public ?string $featureSet;  // Feature set grouping
}
```

## Integration with Laravel Pennant

This package extends Laravel Pennant by providing:

1. **Automatic Discovery** — No need to manually register features
2. **PHP Attributes** — Declarative metadata using native PHP attributes
3. **Rich Metadata** — Add custom metadata to features
4. **Route Integration** — Link features to routes automatically
5. **Structured Data** — Get features as structured data objects
6. **Bulk Operations** — Get all features and their status in one call

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

// Combined with Hoist
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
    "tags": [
      "subscription",
      "pro"
    ],
    "featureSet": "premium"
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

The `href` property in `FeatureData` is generated from the feature's route value (via the `#[Route]` attribute or
`$route` property). The package safely handles routes:

- If no route is defined, `href` will be `null`
- If the route name doesn't exist, `href` will be `null` (no exception thrown)
- If the route name is valid, `href` will contain the generated URL

### The Feature Interface

The package provides an optional `Feature` interface for better type safety. Features are discovered if they either:

1. Implement the `Feature` interface, OR
2. Have a `resolve()` method (for backward compatibility with plain Pennant features)

### Metadata Best Practices

Use metadata for:

- **Categorization** — Group features by category
- **UI Elements** — Icons, colors, badges
- **Permissions** — Access levels, roles
- **Versioning** — Track feature versions
- **Analytics** — Track feature usage

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

## AI Coding Assistant Skill

This package ships a [Laravel Boost](https://skills.laravel.cloud/) skill so coding assistants (Claude Code, Cursor,
etc.) follow the package's conventions when generating code. Install it in your app with:

```bash
php artisan boost:add-skill offload-project/laravel-hoist
```

The skill source lives at [`skills/SKILL.md`](skills/SKILL.md).

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please see the documents below before getting started.

- [Contributing Guide](CONTRIBUTING.md) — setup, workflow, commit conventions, and PR process
- [Code of Conduct](CODE_OF_CONDUCT.md) — expectations for participation in this project

## Security

- [Security Policy](SECURITY.md) — how to report a vulnerability privately

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
