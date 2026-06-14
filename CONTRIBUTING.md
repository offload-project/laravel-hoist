# Contributing to Laravel Hoist

Thanks for your interest in contributing! This document outlines the process and standards for contributing to `offload-project/laravel-hoist`.

## Code of Conduct

By participating in this project, you agree to treat fellow contributors with respect. Be kind, assume good intent, and keep discussions focused on the work.

## Ways to Contribute

- Reporting bugs via the [Bug Report](.github/ISSUE_TEMPLATE/bug_report.md) template
- Proposing new features via the [Feature Request](.github/ISSUE_TEMPLATE/feature_request.md) template
- Improving documentation (`README.md`, `CHANGELOG.md`)
- Fixing bugs or implementing features through pull requests
- Reviewing open pull requests

Before opening a large PR, please open an issue first to discuss the approach.

## Requirements

- PHP **8.3+** (CI matrix runs 8.3, 8.4, 8.5)
- Composer 2
- Laravel Pennant 1+ (a peer dev dependency installed via `composer install`)

## Getting Set Up

1. Fork the repository on GitHub and clone your fork:

   ```bash
   git clone git@github.com:<your-username>/laravel-hoist.git
   cd laravel-hoist
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Install the Git hooks (runs Pint pre-commit, validates Conventional Commits on commit-msg, runs tests and static analysis pre-push):

   ```bash
   composer install-hooks
   ```

4. Create a feature branch off `main`:

   ```bash
   git checkout -b feat/short-description
   ```

## Development Workflow

This package supports Laravel 11, 12, and 13 and PHP 8.3–8.5. Changes must work across that matrix.

### Running the Test Suite

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Tests are written with [Pest](https://pestphp.com/) and live under `tests/`. New behavior should be covered by tests; bug fixes should include a regression test.

### Static Analysis

```bash
composer analyse
```

We use Larastan (PHPStan for Laravel). If you must suppress a finding, prefer narrow ignores via a baseline (`phpstan-baseline.neon`) over loosening the rule set, and explain why in your PR.

### Code Style

```bash
composer pint
```

Pint runs on `pre-commit`. PRs must be Pint-clean — the `code-style.yml` workflow will fail otherwise.

## Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/). The `commit-msg` hook validates this; CI/release tooling depends on it.

Format: `<type>(<optional scope>): <description>`

Common types used in this repo:

| Type         | Use for                                                             |
| ------------ | ------------------------------------------------------------------- |
| `feat`       | New user-facing functionality                                       |
| `fix`        | Bug fixes                                                           |
| `deprecate`  | Marking existing API as deprecated                                  |
| `refactor`   | Internal change with no behavior difference                         |
| `test`       | Adding or updating tests                                            |
| `docs`       | Documentation only                                                  |
| `chore`      | Tooling, dependency bumps, repo housekeeping                        |
| `ci`         | Changes to GitHub Actions workflows                                 |

Examples (taken from this project's history):

- `feat: feature meta attrs`
- `test: code style errs`
- `chore: add php 8.5 test`
- `ci: add merge_commit_sha to release`

Breaking changes: add `!` after the type (e.g., `feat!: rename FeatureData::href`) and explain the migration path in the PR body.

## Pull Requests

1. Make sure your branch is up to date with `main`.
2. Run the full local check before pushing:

   ```bash
   composer pint && composer analyse && composer test
   ```

3. Push your branch and open a PR against `main` using the [PR template](.github/pull_request_template.md).
4. Fill in:
   - What changed and why
   - Type of change (bug fix, feature, breaking, deprecation, etc.)
   - How it was tested (PHP/Laravel/Pennant versions)
   - Whether docs or `CHANGELOG.md` were updated
5. Keep PRs focused. One logical change per PR makes review faster and bisection easier.
6. CI must pass before review:
   - `tests.yml` — Pest across the PHP × Laravel matrix
   - `code-style.yml` — Pint
7. Address review feedback in additional commits rather than force-pushing while review is active.

## Adding or Changing Features

When working on this package, keep these areas in mind:

- **The `Feature` contract** — `OffloadProject\Hoist\Contracts\Feature` is part of the public API. Adding methods to it is breaking; introduce new behavior as optional methods detected via `method_exists()` when possible.
- **PHP attributes** — `Label`, `Description`, `Route`, `Tags`, `FeatureSet` are part of the public API. Renaming, removing, or changing their constructors is breaking. New attributes should be opt-in.
- **`FeatureData`** — this DTO is serialized to JSON in many consumer apps (API endpoints, frontend payloads). Renaming or removing properties is breaking. Adding nullable properties is safe.
- **Facade methods** — `Hoist::all()`, `Hoist::forModel()`, `Hoist::names()`, `Hoist::tagged()`, etc. are documented; keep the docblock on `Hoist` in sync when adding methods to `FeatureDiscovery`.
- **Config** — new keys in `config/hoist.php` must have safe defaults and a comment explaining them.
- **Stubs** — the `hoist:feature` command stub is publishable via `vendor:publish --tag=hoist-stubs`. Both the package stub and the published copy need to work; tokens (`{{ kebab }}`, `{{ label }}`, `{{ class }}`) must remain stable.
- **Pennant compatibility** — features are discovered if they implement `Feature` *or* have a `resolve()` method. Don't tighten this in a way that breaks plain Pennant feature classes.

## Documentation

If your change affects public API, configuration, or usage, update:

- `README.md` — feature list / usage examples
- `CHANGELOG.md` — under the next release section
- `skills/SKILL.md` — the Laravel Boost skill, if conventions change

## Reporting Security Issues

Please do **not** open a public issue for security vulnerabilities. Report them privately via GitHub's "Report a vulnerability" feature on the repository's Security tab so a fix can be coordinated before disclosure.

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE.md) that covers this project.
