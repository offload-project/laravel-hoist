# Security Policy

## Supported versions

Security fixes are applied to the latest minor release line. Older minor versions may receive fixes for critical issues at the maintainers' discretion — when in doubt, please upgrade.

| Version       | Supported              |
| ------------- | ---------------------- |
| `1.3.x`       | ✅                     |
| `1.x` (older) | ⚠️ critical fixes only |
| `< 1.0`       | ❌ (please upgrade)    |

## Reporting a vulnerability

**Please do not open a public GitHub issue for security reports.**

Use [GitHub Security Advisories](https://github.com/offload-project/laravel-hoist/security/advisories/new) to report privately. This lets us discuss, fix, and coordinate disclosure before details become public.

When reporting, please include:

- A description of the issue and its potential impact.
- Steps to reproduce, or a minimal proof-of-concept.
- Affected package version(s), Laravel version, Laravel Pennant version, and PHP version.
- Any suggested fix or mitigation (optional).

## Response expectations

- **Acknowledgement:** within 5 business days.
- **Initial assessment:** within 10 business days.
- **Fix timeline:** depends on severity. Critical issues get prioritized; lower-severity issues may be batched into the next regular release.

We'll keep you updated on progress and credit you in the advisory unless you'd prefer to stay anonymous.

## Scope

Things in scope for this project:

- Vulnerabilities in any code published under `OffloadProject\Hoist\` (the service provider, `FeatureDiscovery` service, `Hoist` facade, `FeatureData` DTO, PHP attributes, the `hoist:feature` console command, and the published stubs).
- Unintended file inclusion or class loading via the directory-scanning logic in `FeatureDiscovery` (e.g., scanning outside configured directories, executing code during discovery).
- Information disclosure via the serialized `FeatureData` payload (e.g., leaking metadata that should not be exposed when returned from a public endpoint by the host app).
- Insecure defaults in the published config or stubs.

Things **not** in scope (please report upstream or with the relevant project):

- Vulnerabilities in Laravel itself, Laravel Pennant, or other Composer dependencies — please file with the respective project.
- Application-level misconfiguration in a consuming app (e.g., exposing `Hoist::all()` results that include sensitive metadata on an unauthenticated route, or pointing `feature_directories` at a path containing untrusted PHP).
- Issues caused by user-supplied implementations of the package's extension points (custom Feature classes with side effects in constructors, custom metadata that includes secrets, etc.).
- Vulnerabilities in the host application's authentication, routing, or database layers.

## Disclosure

Once a fix is published, we will:

1. Publish a GitHub Security Advisory with details and credit.
2. Tag a patch release.
3. Update the changelog with a brief mention (without exploit details prior to the disclosure window).

Thanks for helping keep the project and its users safe.
