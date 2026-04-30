# Altis GitHub Actions CI Configuration

This folder contains the **reusable workflows** that Altis modules use for CI on GitHub Actions. They replace the Travis CI templates that previously lived in `travis/`.

## Workflows

### `module-ci.yml` — replaces `travis/module.yml`

Used by every Altis module repo (`altis-cms`, `altis-media`, `altis-core`, …). Builds an Altis skeleton project, injects the package under test, starts local-server, and runs the Codeception or PHPUnit suite plus docs linting.

### `altis-ci.yml` — replaces `travis/altis.yml`

Used by the skeleton/`empty` package, where the repo *is* the Altis project (rather than a module installed into one). Just runs `composer install`, starts local-server, and runs tests.

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
| `php-version` | `8.3` | |
| `node-version` | `24` | |
| `test-command` | `codecept` | Set to `phpunit` for `altis-dev-tools` itself. |
| `lint-docs` | `true` | Run `dev-tools lintdocs` after tests. |
| `runs-on` | `ubuntu-22.04` | Matches Travis `dist: jammy`. |

`altis-ci.yml`:

| Input | Default |
| --- | --- |
| `php-version` | `8.3` |
| `node-version` | `24` |
| `test-command` | `phpunit` |
| `runs-on` | `ubuntu-22.04` |

## Required secrets (per caller repo)

- `DOCKER_USERNAME` — Docker Hub user (typically `altis`)
- `DOCKER_PASSWORD` — Docker Hub access token

These are the same secrets the previous Travis builds used. Verify with `gh secret list -R humanmade/<repo>`.

## Phases (mirrors the previous Travis flow)

The `module-ci.yml` test job:

1. Determine the base branch — match `vNN-branch` against the trigger ref, otherwise fall back to `dev-master`.
2. `composer create-project altis/skeleton:dev-<branch>` into `$HOME/test-root` (with the base branch as a fallback).
3. `composer require altis/test-theme` and set it as the default theme.
4. `composer require <altis-package>:dev-<branch> as <aliased-version>` to inject the package being tested over any version constraint.
5. Generate `.config/salts.php` from the WordPress salts API and append a load guard to `.config/load.php`.
6. `composer server start`.
7. `composer dev-tools codecept run -p vendor/<altis-package>/tests` (or `phpunit`).
8. `composer dev-tools bootstrap lintdocs` + `composer dev-tools lintdocs -l vendor/<altis-package> all`.
9. On failure, upload Codeception output as a workflow artifact (named `codecept-output-<package>-php<version>`).

## Skipping when there are no tests

The `detect-tests` job checks for `tests/*.suite.yml` files. If none exist, the `test` job is skipped — same early-exit behaviour as the Travis `before_install` check.
