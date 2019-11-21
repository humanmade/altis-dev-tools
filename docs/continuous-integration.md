# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are many benefits to this such as running tests and code linting tools.

[Travis CI](https://travis-ci.com) is the recommended tool for testing with Altis.

A basic Travis configuration is installed automatically that imports the base Altis configuration and your own custom config file from `.config/travis.yml`.

The base configuration will run the following set up steps:

- `composer install`
- `composer local-server start`

From there the tests you run are up to you. A minimal example of your `.config/travis.yml` file might look like the following:

```yml
script:
  - composer dev-tools phpunit
```

[See testing with PHPUnit for more information on the above command](./testing-with-phpunit.md).

Because there is a full instance of Altis running in the CI environment this enables the use of end to end testing using tools like the following:

- [Puppeteer](https://pptr.dev/)
- [Cypress](https://cypress.io)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [aXe accessibility testing](https://www.deque.com/axe/)

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
