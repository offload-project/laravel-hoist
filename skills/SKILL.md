---
name: Laravel Hoist
description: Conventions and APIs for the offload-project/laravel-hoist package — Laravel Pennant feature discovery via attributes, FeatureData payloads, tag filtering, feature sets, and the hoist:feature generator.
compatible_agents:
  - Claude Code
  - Cursor
tags:
  - laravel
  - php
  - pennant
  - feature-flags
  - feature-discovery
  - attributes
---

## Context

`offload-project/laravel-hoist` is a Laravel 11/12/13 package (PHP 8.3+) that extends Laravel Pennant with automatic feature discovery, declarative metadata via PHP attributes, and a structured `FeatureData` payload for serving features to the frontend or to per-user feature dashboards. It ships:

- A `FeatureDiscovery` service exposed via the `Hoist` facade, with methods `discover()`, `all()`, `forModel($model)`, `names()`, and the tag filters `tagged()`, `withTags()`, `withAnyTags()`, plus their `*For($model)` variants that include active status.
- A `Feature` contract (`OffloadProject\Hoist\Contracts\Feature`) — optional but recommended; any class with a `resolve()` method is also picked up for plain-Pennant compatibility.
- Five class-level PHP attributes: `Label`, `Description`, `Route`, `Tags`, `FeatureSet`. Attributes take precedence over equivalent class properties when both are present.
- A `FeatureData` DTO (extending `Spatie\LaravelData\Data`) with `name`, `label`, `description`, `href`, `active`, `metadata`, `tags`, `featureSet`. This is the public serialization shape.
- A `hoist:feature` Artisan command that extends Pennant's `pennant:feature` generator, honors the `feature_directories` config, and supports publishable stubs (`vendor:publish --tag=hoist-stubs`).

Apply this skill when working in a Laravel app that has `offload-project/laravel-hoist` in `composer.json`, or when the user asks for help with `Hoist`, `FeatureDiscovery`, `FeatureData`, the Hoist attributes, or feature classes under `app/Features` in this package.

## Rules

### Feature classes

1. Place feature classes under a directory listed in `config/hoist.php` → `feature_directories`. The map is `[directory => namespace]`; keys are absolute paths (typically built with `app_path()`), values are fully qualified namespaces.
2. Generate features with `php artisan hoist:feature MyFeature` — not `php artisan pennant:feature`. The Hoist command honors the configured directory map and applies the package's stub.
3. Implement `OffloadProject\Hoist\Contracts\Feature` on new feature classes. The interface only requires `resolve(mixed $scope): mixed`, but implementing it gives IDE/type support and makes the class self-documenting. Classes that only define a `resolve()` method (plain Pennant style) are still discovered, but new code should implement the interface.
4. The `$name` public property is what Pennant uses as the feature identifier. Set it to a stable kebab-case string (`'billing'`, `'dark-mode'`). If `$name` is omitted, the class basename is used — prefer setting it explicitly so renaming the class doesn't change the feature identifier.

### Metadata: attributes vs properties

5. Prefer **PHP attributes** over class properties for static metadata. Use properties only when a value must be computed (e.g., reading from another service in the constructor).
6. Available attributes (all target the class, all are `final readonly`):
   - `#[Label('Billing Module')]` — display name
   - `#[Description('Advanced billing features')]` — long-form description
   - `#[Route('billing.index')]` — named route used to generate `href`
   - `#[Tags('subscription', 'pro')]` — variadic; one or more category tags
   - `#[FeatureSet('premium', label: 'Premium Plan')]` — group name; optional label
7. When both an attribute and the equivalent property are present, **the attribute wins**. Don't rely on the property-side overriding the attribute.
8. Custom metadata that doesn't fit a first-class attribute belongs in `metadata(): array`. Use it for icons, colors, version stamps, plan tiers, nav flags, etc. — not for the well-known fields above.

### Accessing features in app code

9. Reach for the facade — `OffloadProject\Hoist\Facades\Hoist` — rather than injecting `FeatureDiscovery` directly. The facade and service are equivalent, but the facade reads more naturally in controllers, views, and console commands.
10. Use `Hoist::forModel($user)` when you need active status. `Hoist::all()` returns features with `active` as `null` — fine for listings/admin views, wrong for "what can this user see right now."
11. The `FeatureData` shape is the public serialization contract. When returning features from an API endpoint, return the collection directly (`return Hoist::forModel($user);`) — Spatie Laravel Data handles serialization. Don't manually `->toArray()` and reshape.
12. `$feature->href` is `null` when the `#[Route]` value is missing **or** when the route name doesn't resolve. Treat `href` as optional in templates — guard with `@if($feature->href)`.

### Filtering by tag

13. Use the tag helpers instead of filtering the result of `all()` yourself:
    - Single tag → `Hoist::tagged('flag')`
    - ALL tags (AND) → `Hoist::withTags(['subscription', 'pro'])`
    - ANY tag (OR) → `Hoist::withAnyTags(['pro', 'enterprise'])`
14. Use the `*For($model)` variants (`taggedFor`, `withTagsFor`, `withAnyTagsFor`) whenever you need both a tag filter and per-user active status. Don't chain `tagged()->map(active = ...)` manually — the `For` variants resolve Pennant in one pass.
15. Tags are a convention layer for *grouping*, not for *authorization*. Don't gate access on "feature has the `pro` tag" — gate on `Feature::active()` (or `$feature->active`), which is what Pennant actually evaluates.

### Pennant interop

16. `Hoist` is additive — Laravel Pennant's native API (`Feature::active()`, `@feature` Blade directive, `Feature::for($user)->active()`, route middleware) continues to work unchanged for any class discovered by Hoist.
17. Don't manually call `Feature::define()` for Hoist-discovered classes. The class IS the definition; `resolve()` is what Pennant calls.

### Config

18. The default `feature_directories` is `[app_path('Features') => 'App\\Features']`. For multi-module apps, list each module's directory + namespace explicitly. Don't try to scan `app/` recursively.
19. Stubs are publishable (`vendor:publish --tag=hoist-stubs`) to `stubs/hoist-feature/`. The stub must keep the `{{ class }}`, `{{ kebab }}`, and `{{ label }}` tokens — the command rewrites both `{{ token }}` and `{{token}}` forms.
20. Discovery caches results in the `FeatureDiscovery` instance (it's bound as a singleton). In a long-running worker (Octane, Reverb), re-deploying with new feature classes requires a restart for them to be picked up — discovery does not rescan the filesystem between requests.

### Don't edit vendor

21. Don't edit files inside `vendor/offload-project/laravel-hoist`. All extension points are exposed via `config/hoist.php`, the publishable stubs, and your own feature classes / attributes.

## Examples

### Create a feature with attributes

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
final class BillingFeature implements Feature
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
        ];
    }
}
```

### Serve features to the frontend

```php
use Illuminate\Http\Request;
use OffloadProject\Hoist\Facades\Hoist;

Route::get('/api/features', function (Request $request) {
    return Hoist::forModel($request->user());
});
```

### Build a navigation from active features

```php
$nav = Hoist::forModel($request->user())
    ->filter(fn ($feature) => $feature->active && $feature->href)
    ->filter(fn ($feature) => $feature->metadata['show_in_nav'] ?? true);
```

### Filter by tag with active status

```php
// All "subscription" features for the current user, with active status
$subs = Hoist::taggedFor('subscription', $request->user());

// Only features that have BOTH "subscription" AND "pro" tags
$pro = Hoist::withTagsFor(['subscription', 'pro'], $request->user());

// Anything on a paid tier
$paid = Hoist::withAnyTagsFor(['pro', 'enterprise'], $request->user());
```

### Multi-module feature directories

```php
// config/hoist.php
return [
    'feature_directories' => [
        app_path('Authorization/Features') => 'App\\Authorization\\Features',
        app_path('Billing/Features')       => 'App\\Billing\\Features',
        app_path('Admin/Features')         => 'App\\Admin\\Features',
    ],
];
```

### Blade dashboard

```blade
@foreach ($features as $feature)
    <div class="feature-card {{ $feature->active ? 'active' : 'inactive' }}">
        <h3>{{ $feature->label }}</h3>
        <p>{{ $feature->description }}</p>

        @if ($feature->active && $feature->href)
            <a href="{{ $feature->href }}" class="btn">Go to {{ $feature->label }}</a>
        @endif
    </div>
@endforeach
```

### Pennant interop

```php
use Laravel\Pennant\Feature;

if (Feature::active('billing')) {
    // ...
}

// In Blade
@feature('billing')
    <!-- ... -->
@endfeature
```

## Anti-patterns

- ❌ `php artisan pennant:feature MyFeature` — bypasses the Hoist directory map and the Hoist stub. Use `hoist:feature`.
- ❌ Setting both a `#[Label]` attribute and a `$label` property and expecting the property to override. The attribute always wins.
- ❌ Calling `Feature::define('billing', ...)` for a class that's already discovered by Hoist. Define-by-class is the convention; manual `define()` calls duplicate it.
- ❌ Using `Hoist::all()` when you need per-user state. `all()` returns `active = null`. Use `forModel($user)` instead.
- ❌ Filtering the result of `Hoist::all()->filter(...)` to get a tag subset. Use `Hoist::tagged()` / `withTags()` / `withAnyTags()` — and the `*For($model)` variants when you also need active status.
- ❌ Treating tags as authorization. Tags are categorization. Gate on `$feature->active` (Pennant's actual evaluation), not "has the `pro` tag."
- ❌ Subclassing `FeatureData` or building your own DTO and returning that from API endpoints. `FeatureData` is the public serialization shape; the package's facade methods already return it.
- ❌ Editing files inside `vendor/offload-project/laravel-hoist`. Extend via `config/hoist.php`, publishable stubs, and your own feature classes / attributes.
- ❌ Relying on filesystem rescans between requests in a long-running worker (Octane, RoadRunner, Reverb). Discovery is cached per instance — restart workers after deploying new feature classes.

## References

- Repository: <https://github.com/offload-project/laravel-hoist>
- README: <https://github.com/offload-project/laravel-hoist/blob/main/README.md>
- Laravel Pennant: <https://laravel.com/docs/pennant>
- Laravel Boost skills: <https://skills.laravel.cloud/>
