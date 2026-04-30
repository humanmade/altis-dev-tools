# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are many benefits to this such as
running tests and code linting tools.

[GitHub Actions](https://docs.github.com/actions) is the recommended tool for testing Altis modules. Altis provides a reusable
workflow at `humanmade/altis-dev-tools/.github/workflows/altis-ci.yml` that handles `composer install`, `composer local-server
start`, and runs your tests.

A minimal `.github/workflows/ci.yml` for an Altis project looks like this:

```yml
name: CI

on:
  push:
    branches: [master, main, 'v*-branch']
  pull_request:
    branches: [master, main, 'v*-branch']

jobs:
  ci:
    uses: humanmade/altis-dev-tools/.github/workflows/altis-ci.yml@<sha>
    with:
      test-command: phpunit       # or 'codecept'
    secrets:
      DOCKER_USERNAME: ${{ secrets.DOCKER_USERNAME }}
      DOCKER_PASSWORD: ${{ secrets.DOCKER_PASSWORD }}
```

Pin `<sha>` to a commit on `humanmade/altis-dev-tools`. The workflow runs the following set up steps for you:

- `composer install`
- `composer local-server start`

From there the tests are up to you. The most common command is `composer dev-tools phpunit` or `composer dev-tools codecept run`.

See [testing with PHPUnit](./testing-with-phpunit.md) and [testing with Codeception](./testing-with-codeception.md) for more
information on the above commands.

We recommend reading and bookmarking the following:

- [GitHub Actions documentation](https://docs.github.com/actions)
- [Reusing workflows](https://docs.github.com/actions/using-workflows/reusing-workflows)

Because there is a full instance of Altis running in the CI environment this enables the use of end to end testing using tools like
the following:

- [Puppeteer](https://pptr.dev/)
- [Cypress](https://cypress.io)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [aXe accessibility testing](https://www.deque.com/axe/)

## Using Travis CI

Travis CI was the previous default and remains a fully supported option for Altis projects. The skeleton no longer ships a Travis
template by default, but you can continue to use Travis on your own project by maintaining a `.travis.yml` file at the repository
root. The previous module template lived at `humanmade/altis-dev-tools:travis/altis.yml` and ran the same `composer install` /
`composer local-server start` setup; it is no longer maintained but the equivalent commands are documented above and the `ci`
environment overrides below apply equally to both Travis and GitHub Actions runs.

## Overriding Config for CI

In some cases you won't need certain modules or services for your test runs. For example, switching off cavalcade will result in the
containers for those services to not be downloaded and started, resulting in faster test runs.

There is a special environment type you can use to override the configuration in CI environments which extends the `local`
environment settings if any. Use the key `ci` to apply these settings:

```json
{
    "name": "example/my-site",
    "require": {
        "altis/altis": "*"
    },
    "extra": {
        "altis": {
            "environments": {
                "ci": {
                    "modules": {
                        "cloud": {
                            "cavalcade": false
                        }
                    }
                }
            }
        }
    }
}
```

Similarly, you can disable the following services:

- X-ray
- Elasticsearch
- Kibana
- Tachyon

To disable X-ray, set the following configuration:

```json
{
    "extra": {
        "altis": {
            "environments": {
                "ci": {
                    "modules": {
                        "cloud": {
                            "xray": false
                        }
                    }
                }
            }
        }
    }
}
```

To disable Elasticsearch and Kibana, set the following configuration:

```json
{
    "extra": {
        "altis": {
            "environments": {
                "ci": {
                    "modules": {
                        "cloud": {
                            "elasticsearch": false
                        },
                        "local-server": {
                            "elasticsearch": false
                        }
                    }
                }
            }
        }
    }
}
```

Note: Disabling Elasticsearch will automatically disable Kibana. But if you want to disable Kibana only, you can do so like this:

```json
{
    "extra": {
        "altis": {
            "environments": {
                "ci": {
                    "modules": {
                        "local-server": {
                            "kibana": false
                        }
                    }
                }
            }
        }
    }
}
```

To disable Tachyon, set the following configuration:

```json
{
    "extra": {
        "altis": {
            "environments": {
                "ci": {
                    "modules": {
                        "media": {
                            "tachyon": false
                        }
                    }
                }
            }
        }
    }
}
```

## Conditional Builds

By default builds will run any time code is pushed to the repository or a pull request is opened. To restrict when a workflow
runs, use the standard GitHub Actions [`on` triggers](https://docs.github.com/actions/using-workflows/events-that-trigger-workflows)
and [path/branch filters](https://docs.github.com/actions/using-workflows/workflow-syntax-for-github-actions#filter-pattern-cheat-sheet).
A few common setups:

```yaml
# Only run on pull requests
on:
  pull_request:

# Only run on specific branches (push and PRs targeting them)
on:
  push:
    branches: [staging, development]
  pull_request:
    branches: [staging, development]

# Skip the workflow on commits that only touch documentation
on:
  push:
    paths-ignore:
      - 'docs/**'
      - '*.md'
```

If you are still on Travis CI, the equivalent feature is [conditional builds](https://docs.travis-ci.com/user/conditions-v1):

```yaml
# Only run on pull requests
if: type = pull_request

# Only run on specific branches
if: branch IN (staging, development)
```

## Overriding The Base Config

For most projects the reusable workflow is enough. If you need to override or extend it — extra steps, custom matrix, services,
artifacts — copy the steps from `humanmade/altis-dev-tools/.github/workflows/altis-ci.yml` directly into your own
`.github/workflows/ci.yml` and modify them as needed. The reusable workflow's source is the canonical reference.

If you need different behaviour, the recommended pattern is to call the reusable workflow for the standard test job and add
your own jobs alongside it:

```yaml
jobs:
  ci:
    uses: humanmade/altis-dev-tools/.github/workflows/altis-ci.yml@<sha>
    secrets:
      DOCKER_USERNAME: ${{ secrets.DOCKER_USERNAME }}
      DOCKER_PASSWORD: ${{ secrets.DOCKER_PASSWORD }}

  e2e:
    needs: ci
    runs-on: ubuntu-22.04
    steps:
      # your own end-to-end / Cypress / Lighthouse steps
```

When you override the base config we cannot guarantee functionality or support any issues you may encounter.
