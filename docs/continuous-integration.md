# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are many benefits to this such as
running tests and code linting tools.

[Travis CI](https://travis-ci.com) is the recommended tool for testing with Altis.

A basic Travis configuration is installed automatically that imports the base Altis configuration and your own custom config file
from `.config/travis.yml`.

Configuration in `.config/travis.yml` will be merged into the Altis base config using a recursive-merge-append approach.

The base configuration will run the following set up steps:

- `composer install`
- `composer server start`

From there the tests you run are up to you. A minimal example of your `.config/travis.yml` file might look like the following:

```yml
script:
    - composer dev-tools phpunit
    # and/or
    - composer dev-tools codecept run
```

See [testing with PHPUnit](./testing-with-phpunit.md) and [testing with Codeception](./testing-with-codeception.md) for more
information on the above commands.

We recommend reading and bookmarking the following:

- [Travis CI documentation](https://docs.travis-ci.com/)
- [Travis config file reference](https://config.travis-ci.com/)

Because there is a full instance of Altis running in the CI environment this enables the use of end to end testing using tools like
the following:

- [Puppeteer](https://pptr.dev/)
- [Cypress](https://cypress.io)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [aXe accessibility testing](https://www.deque.com/axe/)

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

By default builds on Travis will run any time code is pushed to the repository or merged regardless of branch. You can use
Travis' [conditional builds feature](https://docs.travis-ci.com/user/conditions-v1) to determine when a build should run. The
following configuration examples show how to achieve some common set ups:

```yaml
# Only run on pull requests
if: type = pull_request

# Only run on specific branches (and pull requests to those branches)
if: branch IN (staging, development)

# Combining the above
if: type = pull_request AND branch IN (staging, development)
```

There are many built in values and types of operator you can make use of in your config, as well
as [setting conditions per stage for multistage builds](https://docs.travis-ci.com/user/build-stages/).

## Migrating From An Existing Travis Config

If you already have an existing Travis configuration then follow these steps:

1. Copy `.travis.yml` to `.config/travis.yml`
1. Compare this file to the one in `vendor/altis/dev-tools/travis/altis.yml`:

- Remove any duplicate items from `.config/travis.yml`
- Remove additional PHP versions if you have a PHP version matrix
- Update any `phpunit` commands to use `composer dev-tools phpunit`. If you pass any arguments to `phpunit` you must separate them
  with the options delimiter `--` e.g. `composer dev-tools phpunit -- [options]`

1. Commit these changes to a branch and create a pull request
1. Confirm that the build works and tests pass

Please contact support if you require any assistance with migrating.

## Overriding The Base Config

If you need to fully override the base config you can remove the line importing `humanmade/altis-dev-tools:travis/altis.yml` and
committing the change. You can then add your full configuration to `.config/travis.yml`.

**Note:** Altis will emit a warning on `composer install` or `composer update` if the `.travis.yml` doesn't match what's expected
but no error codes are returned and you can safely ignore this.

When overriding the base Travis config we cannot guarantee functionality within Travis or support any issues you may encounter.
