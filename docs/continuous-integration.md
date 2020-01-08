# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are many benefits to this such as running tests and code linting tools.

[Travis CI](https://travis-ci.com) is the recommended tool for testing with Altis.

A basic Travis configuration is installed automatically that imports the base Altis configuration and your own custom config file from `.config/travis.yml`.

Configuration in `.config/travis.yml` will be merged into the Altis base config using a recursive-merge-append approach.

The base configuration will run the following set up steps:

- `composer install`
- `composer server start`

From there the tests you run are up to you. A minimal example of your `.config/travis.yml` file might look like the following:

```yml
script:
  - composer dev-tools phpunit
```

[See testing with PHPUnit for more information on the above command](./testing-with-phpunit.md).

We recommend reading and bookmarking the following:

- [Travis CI documentation](https://docs.travis-ci.com/)
- [Travis config file reference](https://config.travis-ci.com/)

Because there is a full instance of Altis running in the CI environment this enables the use of end to end testing using tools like the following:

- [Puppeteer](https://pptr.dev/)
- [Cypress](https://cypress.io)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [aXe accessibility testing](https://www.deque.com/axe/)

## Conditional Builds

By default builds on Travis will run any time code is pushed to the repository or merged regardless of branch. You can use Travis' [conditional builds feature](https://docs.travis-ci.com/user/conditions-v1) to determine when a build should run. The following configuration examples show how to achieve some common set ups:

```yaml
# Only run on pull requests
if: type = pull_request

# Only run on specific branches (and pull requests to those branches)
if: branch IN (staging, development)

# Combining the above
if: type = pull_request AND branch IN (staging, development)
```

There are many built in values and types of operator you can make use of in your config, as well as [setting conditions per stage for multistage builds](https://docs.travis-ci.com/user/build-stages/).

## Migrating From An Existing Travis Config

If you already have an existing Travis configuration then follow these steps:

1. Copy `.travis.yml` to `.config/travis.yml`
1. Compare this file to the one in `vendor/altis/dev-tools/travis/altis.yml`:
  - Remove any duplicate items from `.config/travis.yml`
  - Remove additional PHP versions if you have a PHP version matrix
  - Update any phpunit commands to use `composer dev-tools phpunit`. If you pass any arguments to `phpunit` you must separate them with the options delimiter `--` eg. `composer dev-tools phpunit -- [options]`
1. Commit these changes to a branch and create a pull request
1. Confirm that the build works and tests pass

Please contact support if you require any assistance with migrating.

## Overriding The Base Config

If you need to fully override the base config you can remove the line importing `humanmade/altis-dev-tools:travis/altis.yml` and committing the change. You can then add your full configuration to `.config/travis.yml`.

**Note:** Altis will emit a warning on `composer install` or `composer update` if the `.travis.yml` doesn't match what's expected but no error codes are returned and you can safely ignore this.

When overriding the base Travis config we cannot guarantee functionality within Travis or support any issues you may encounter.
