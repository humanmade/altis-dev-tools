# Testing With PHPUnit

PHPUnit is a powerful framework for testing PHP code and applications. Tests can be broken down into two fundamental types:

- Unit tests
- Integration tests

Unit tests can be run against a unit of code in isolation, such as a function or object and require no persistent state.

Integration tests are useful for testing code that interacts with a running application. In the case of Altis integration tests are often more useful however it is possible to run both types of test.

## Setting Up PHPUnit

To set up PHPUnit from scratch run the following command:

```sh
composer dev-tools scaffold phpunit
```

This will create the following files:

- `phpunit.xml.dist` - Instructions for PHPUnit
- `tests/bootstrap.php` - Handles loading and installing Altis before running tests.
- `tests/config.php` - A custom config file used to define constants or other code for the test run.
- `tests/test-sample.php` - A basic test to get you started.

If you already have PHPUnit set up for a different environment you can temporarily
