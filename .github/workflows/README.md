# Altis GitHub Actions CI Configuration

This folder contains the **reusable workflows** that Altis modules use for CI on GitHub Actions. They replace the Travis CI templates that previously lived in `travis/`.

## Workflows

### `module-ci.yml` — replaces `travis/module.yml`

Used by every Altis module repo (`altis-cms`, `altis-media`, `altis-core`, …). Builds an Altis skeleton project, injects the package under test, starts local-server, and runs the Codeception or PHPUnit suite plus docs linting.

### `altis-ci.yml` — replaces `travis/altis.yml`

Used by the skeleton/`empty` package and by end-user Altis projects, where the repo *is* the Altis project (rather than a module installed into one). Just runs `composer install`, starts local-server, and runs tests.

The thin caller for end-user projects is shipped as the template at [`templates/project-ci.yml`](../../templates/project-ci.yml). The `altis/dev-tools-command` composer plugin copies it to `.github/workflows/ci.yml` on `composer create-project altis/skeleton` and re-pins the `@<ref>` to the installed dev-tools version on every `composer install` / `composer update` (until the user customises the file).

## Calling the reusable workflow

Each module repo holds a thin caller at `.github/workflows/ci.yml`:

```yaml
name: CI
on:
  push:
    branches: [master, main, 'v*-branch']
  pull_request:
    branches: [master, main, 'v*-branch']

jobs:
  ci:
    uses: humanmade/altis-dev-tools/.github/workflows/module-ci.yml@<sha>
    with:
      altis-package: altis/cms          # the composer name
      # test-command: phpunit          # default is 'codecept'
      # lint-docs: false               # default is true
    secrets:
      DOCKER_USERNAME: ${{ secrets.DOCKER_USERNAME }}
      DOCKER_PASSWORD: ${{ secrets.DOCKER_PASSWORD }}
```

Pin to a SHA from this repo (mirrors the `travis/module.yml@<sha>` pattern). The bulk-update helper at `humanmade/altis/scripts/update-module-gha-ref.sh` opens PRs across module repos to bump the SHA.

## Inputs

`module-ci.yml`:

| Input | Default | Notes |
| --- | --- | --- |
| `altis-package` | *(required)* | Composer name (e.g. `altis/cms`). Used to install the package and locate its tests under `vendor/<altis-package>/tests`. |
| `php-version` | `8.4` | |
| `node-version` | `24` | |
| `test-command` | `codecept` | Set to `phpunit` for `altis-dev-tools` itself. |
| `lint-docs` | `true` | Run `dev-tools lintdocs` after tests. |
| `runs-on` | `ubuntu-22.04` | Matches Travis `dist: jammy`. |

`altis-ci.yml`:

| Input | Default |
| --- | --- |
| `php-version` | `8.4` |
| `node-version` | `24` |
| `test-command` | `phpunit` |
| `runs-on` | `ubuntu-22.04` |

## Required secrets (per caller repo)

- `DOCKER_USERNAME` — Docker Hub user (typically `altis`)
- `DOCKER_PASSWORD` — Docker Hub access token

These are the same secrets the previous Travis builds used. Verify with `gh secret list -R humanmade/<repo>`.
When these secrets are unavailable in untrusted contexts (for example Dependabot pull requests), the Docker login step is skipped.

## Phases (mirrors the previous Travis flow)

The `module-ci.yml` test job:

1. Determine the base branch — match `vNN-branch` against the PR target ref (or the pushed branch on push events), otherwise fall back to `master`.
2. `composer create-project altis/skeleton:dev-<branch>` into `$HOME/test-root` (with the base branch as a fallback).
3. `composer require altis/test-theme` and set it as the default theme.
4. Register `humanmade/altis-<name>.git` as a `vcs` repository for the package under test, bypassing Packagist (whose CDN can lag behind a freshly pushed branch).
5. `composer require <altis-package>:dev-<branch> as <aliased-version>` to inject the package being tested over any version constraint.
6. Generate `.config/salts.php` from the WordPress salts API and append a load guard to `.config/load.php`.
7. `composer server start`.
8. `composer dev-tools codecept run -p vendor/<altis-package>/tests` (or `phpunit`).
9. `composer dev-tools bootstrap lintdocs` + `composer dev-tools lintdocs -l vendor/<altis-package> all`.
10. On failure, upload Codeception output as a workflow artifact (named `codecept-output-<package>-php<version>`).

## Skipping when there are no tests

The `detect-tests` job checks for `tests/*.suite.yml` files. If none exist, the `test` job is skipped — same early-exit behaviour as the Travis `before_install` check.

## Workflow logic mirror tests

Three shell scripts under [`tests/`](tests/) exercise the inline shell snippets in `module-ci.yml` (and `altis-ci.yml`) so the logic can be checked without round-tripping through GitHub Actions. They run in the `bootstrap-tests` job in [`ci.yml`](ci.yml), and can also be run locally for pre-commit verification when changing the workflow.

- `ci-branch-resolution.sh` — mirrors the "Compute base branch" step. Runs a matrix of `push` and `pull_request` scenarios across `master`, `v##-branch`, and arbitrary feature branches.
- `ci-version-resolution.sh` — mirrors the `composer require … as <alias>` derivation. Builds synthetic `composer.lock` fixtures and checks the resolved alias, including packages found under `.packages-dev` and `dev-*` installed versions.
- `ci-php-detection.sh` — mirrors the "Detect PHP version" step. Builds synthetic `composer.json` fixtures and checks the resolved runner PHP, covering `config.platform.php`, patch-level trimming to major.minor, the fallback default, and the `php-version` override.

Each script supports two modes:

```bash
# Run the full test matrix.
.github/workflows/tests/ci-branch-resolution.sh
.github/workflows/tests/ci-version-resolution.sh
.github/workflows/tests/ci-php-detection.sh

# Evaluate one ad-hoc input.
HEAD_REF=feat/foo BASE_REF=v25-branch  .github/workflows/tests/ci-branch-resolution.sh --eval
LOCK_FILE=./composer.lock PKG=altis/cms .github/workflows/tests/ci-version-resolution.sh --eval
COMPOSER_JSON=./composer.json FALLBACK=8.4 .github/workflows/tests/ci-php-detection.sh --eval
```

Each script duplicates the shell logic from `module-ci.yml`/`altis-ci.yml`. A shared source file would not work because the reusable workflow's `actions/checkout@v4` checks out the caller's repo, not this one. The YAML steps carry comments pointing at these scripts to flag drift on review.

## CI bootstrap guard

Two further scripts under [`tests/`](tests/) guard the project CI bootstrap — installing this package scaffolds a project's `.github/workflows/ci.yml` from [`templates/project-ci.yml`](../../templates/project-ci.yml) via `altis/dev-tools-command`. They run in the same `bootstrap-tests` job in [`ci.yml`](ci.yml).

- `ci-template-constraint.sh` — fast, no network: checks the `altis/dev-tools-command` constraint admits the release that consumes the template.
- `ci-bootstrap-install.sh` — real `composer install` of a throwaway fixture, asserting `ci.yml` is scaffolded and pinned. Needs `composer` + network.

```bash
.github/workflows/tests/ci-template-constraint.sh
.github/workflows/tests/ci-bootstrap-install.sh
```
