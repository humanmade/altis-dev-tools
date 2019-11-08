# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are may benefits to this including running tests and code linting tools.

[Travis CI](https://travis-ci.com) is the recommended tool for testing with Altis

To set up a basic Travis configuration run the following command:

```sh
composer dev-tools scaffold travis
```

The default configuration will run the following set up steps:

- `composer install`
- `composer local-server start`

Edit the `script` section of the generated file to run your tests. For example you can use the built in `composer dev-tools phpunit` command to run PHPUnit.

Because you have a full instance of Altis running in the CI environment this enables creating and running end to end tests using tools like [Puppeteer](https://pptr.dev/) and [Cypress](https://cypress.io).
