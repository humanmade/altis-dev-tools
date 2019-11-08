# Continuous Integration

It is common practice to use some form of Continuous Integration service on projects. There are may benefits to this including running tests and code linting tools.

[Travis CI](https://travis-ci.com) is the recommended tool for testing with Altis.

To set up a basic Travis configuration run the following command:

```sh
composer dev-tools scaffold travis
```

The default configuration will run the following set up steps:

- `composer install`
- `composer local-server start`

Edit the `script` section of the generated file to run your tests. The default test to run is `composer dev-tools phpunit`. [See testing with PHPUnit for more information](./testing-with-phpunit.md).

Because there is a full instance of Altis running in the CI environment this enables the use of end to end testing using tools like the following:

- [Puppeteer](https://pptr.dev/)
- [Cypress](https://cypress.io)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [aXe accessibility testing](https://www.deque.com/axe/)
