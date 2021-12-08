
# Testing with Codeception

[Codeception](https://codeception.com/) is an extensible testing framework for PHP applications, with modular architecture, built on-top of PHPUnit, with modules that enable multiple [different types of testing](#test-types).

[wp-browser](https://wpbrowser.wptestkit.dev/) is used to provide WordPress-specific modules and helpers to facilitate setting up and running tests for WordPress themes, plugins, and whole sites.

Note that while Codeception is typically geared towards acceptance and functional tests, it can also run unit and integration tests, replacing the need for separate Codeception and PHPUnit test suites.

# Table of contents

- [Getting started](#getting-started)
  - [Quick start](#quick-start)
- [Running tests](#running-tests)
  - [Advanced usage](#advanced-usage)
  - [Continuous Integration](#continuous-integration)
- [Writing tests](#writing-tests)
  - [Terminology](#terminology)
  - [Test directory structure](#test-directory-structure)
  - [Test types](#test-types)
    - [Acceptance tests](#acceptance-tests)
    - [Functional tests](#functional-tests)
    - [Integration tests](#integration-tests)
    - [Unit tests](#unit-tests)
    - [WordPress unit tests](#wordpress-unit-tests)
  - [Dependency Injection](#dependency-injection)
  - [Annotations](#annotations)
  - [Available Modules](#available-modules)
  - [Altis Helpers](#altis-helpers)
- [Scaffolding](#scaffolding)
- [Advanced usage](#advanced-usage)
  - [Debugging](#debugging)
  - [Extensions](#extensions)
  - [Custom config](#custom-config)


## Getting started

Altis provides a zero-configuration approach for setting up and running Codeception tests, so you can start writing and running tests right-away!

If you're already familiar with Codeception and wp-browser, you can start [boostrapping and scaffolding tests](#scaffolding), and [run them](#running-tests) via the zero-config command, they'll just work!

```sh
# Bootstrap the tests directory and create all default suites
composer dev-tools codecept bootstrap

# Generate an acceptance test class
composer dev-tools codecept generate:cest acceptance awesome-feature/admin/AwesomeFeatureAdminTest

# Run tests!
composer dev-tools codecept run
```

For some extra control, check out the available [advanced usage](#advanced-usage) for some fine-tuning.

## Running Tests

**Note:** Codeception setup is currently only available while using the Local Server module, with no support for Local Chassis.

In order to run Codeception tests, you can run the following shorthand command:

```sh
composer dev-tools codecept run
```

This assumes you have tests in the root `tests` directory. Check the [Advanced usage](#advanced-usage) section below for command options.

### Advanced usage

There are multiple available options to customize the running command, eg:

```sh
composer dev-tools codecept [-p PATH/TO/TESTS] [-o PATH/TO/OUTPUT] [-b BROWSER] [-a] run [TEST-SUITE] [[TestClass]:testMethod]
```

- `-p/--path` defines the directory where tests exists. Omit to use the `tests` root directory.
- `-o/--output` defines the path to store artifacts from the test running process. Omit to use/create the `_output` directory within the chosen tests path.
- `-b/--browser` defines which browser to use for acceptance tests. Omit to use the default browser. Possible parameters are `chrome` (default), and `firefox`.
- `-a/--all` runs all testing suites despite any failure, otherwise fails/stops on the first failing suite (default).
- `TEST-SUITE` references the name of the test suite to run, typically one of the `*.suite.yml` files in the tests directory. Omit to run all found test suites.
- `TestClass` references one of the test classes in the specified suite. Omit to run all tests within the suite(s).
- `testMethod` references a single test method within the specified test class. Omit to run all test method within specified test class(es)/suite(s).

Altis `codecept` command proxies commands to the `codeception` CLI, except for the `-b` and `-p` parameters, so you'll be able to execute advanced commands and utilize more of what Codeception has to offer as needed. To generate a suite or a test for example you could run the following command:

```sh
composer dev-tools codecept generate g:cest TEST-SUITE TestClassName
```

To pass arbitary options to `codeception` command, eg: `-vv` to enable verbose mode, use the options delimiter `--` to split those as follows:

```sh
composer dev-tools codecept run -- -vv
```

When you invoke the `codecept run` command, this happens in the background:
- Altis looks within the tests directory/directories for test suites files, and runs through each suite one by one in separate threads.
- For suites using the WebDriver module, Altis boots up a docker container with a headless browser to execute those tests, based on Selenium standalone web driver containers.
- For suites using the WPDb module, Altis sets up test databases, and seeds them with a bundled sample dump file.
- Tests output, eg: failed tests screenshots/html snapshots, and debugging artifacts, are saved to `PROJECT/ROOT/tests/_output` for convenience.
- After tests have run, Altis removes the test databases, clears test caches, and the browser container.

### Continuous Integration

In order to run Codeception tests in Continous Integration environments, follow the [documentation on setting up Continous Integration on Travis](https://docs.altis-dxp.com/dev-tools/continuous-integration/), and specify your test running command(s) as per the documentation above, typically using `composer dev-tools codecept run` instead of / in addition to `composer dev-tools phpunit` as explained in the docs.

## Writing Tests

### Terminology

- **Test suite**
Collection of test classes, sharing running configuration and testing environment like modules, helpers, and constants, that typically runs in the same thread. Defined by a suite definition file, eg: `acceptance.suite.yml`, and a neighbouring folder that hosts related tests, with the same name as the suite, eg: `acceptance`.
- **Test class**
Collection of tests for a certain functionality, or one aspect of it, typically combined in the same class. Defined by a class file, eg: `class-test-authorship-admin.php`.
- **Test**
Individual test methods within a test class, that typically tests a single specific scenario. eg: `test_user_can_signup` or `test_submission_invalid_email`.
- **Actor**
A Codeception actor is the main driver of acceptance or functional tests, whose methods typically come from the defined modules and helpers in the test suite configuration, typically referred to as `$I`. [Read more on Codeception docs](https://codeception.com/docs/06-ReusingTestCode.html#What-are-Actors).
- **Module**
Codeception modules extend its functionality or environment, and provide related methods that can be used within tests. For instance:
  - `WPDb` provides methods to allow accessing the database and inserting or updating objects, and also enables importing a base database snapshot using a `.sql` file.
  - `WPLoader` provides a bootstrapped WordPress environment
  - `Asserts` provides the commonly used `Symfony\Asserts` methods, eg: `assertEquals`.
  [Read more about modules in the Codeception docs](https://codeception.com/docs/06-ModulesAndHelpers#Standard-Modules).
- **Helper**
Codeception helpers are classes that provide commonly used actions and assertions to Actors, eg: `$I->havePostInDatabase()` which creates a new post in the database, using the `WPDb` module. Those are typically located in the `_helpers` directory. [Read more about helpers on the Codeception docs](https://codeception.com/docs/06-ModulesAndHelpers#Helpers).
- **Environment**
Codeception environments are sets of configurations that allows specifying different environment setups, modules, helpers, etc, to be able to run tests in different, well, environments! eg: running tests in Firefox vs Chrome, in Linux vs Windows, etc. Codeception allows defining environments in test suites or in dedicated shared files, eg: `_envs/chrome.yml`. [Read more about environments on the Codeception docs](https://codeception.com/docs/07-AdvancedUsage.html#Environments).
- **PageObject**
Codeception's `PageObject` is a special type of helper that represents a specific web page and/or template, where you define constants and actions for interacting with that page/template to be able to use it in different tests. This makes it easier to write tests, and to refactor actions based on changing templates. For instance, a `LoginPage` helper would define the CSS and XPath selectors for forms and buttons, as well as the actions needed to login to a site. [Read more about the page object on the Codeception docs](https://codeception.com/docs/06-ReusingTestCode.html#PageObjects).
- **StepObject**
Codeception's `StepObject` is a special type of helper that represents a set of actions common to a role or area of functionality, eg: `Admin` can represent actions that an Admin user can do, eg: `loginAsAdmin` or `activatePlugin`. [Read more about step objects on the Codeception docs](https://codeception.com/docs/06-ReusingTestCode.html#StepObjects).

### Test directory structure

Codeception tests are split into suites, each suite is defined by a file, eg: `acceptance.suite.yml`, and a tests directory with the same suite name, eg: `acceptance`, that hosts test files. eg:

```yml
tests/
- acceptance/
    - Signup/
        - SignupSubmissionTest.php
- integration/
    - Signup/
        - SignupSubmissionHandlingTest.php
- acceptance.suite.yml
- integration.suite.yml
```

Typical suite configuration includes the main actor, modules, helpers, and extensions used by the suite, eg:

```yml
# acceptance.suite.yml

actor: AcceptanceTester
modules:
    enabled:
        - WPDb
        - WPWebDriver
        - Asserts
        - \Helper\Acceptance
```

### Test types

To start writing tests, you need to decide which type of tests you need from the typical types available below. You can mix and match different types of tests to satisfy the project needs.

#### Acceptance tests

In short: *Testing a scenario from a user perspective, **in the browser***, ie: opening signup page, typing credentials, clicking sign in, and checking browser output.

This type uses a browser, where a web driver drives the browser clicking and typing to simulate user actions.

These can be written in _CEPT_ format, eg:

```php
<?php
// SignupSubmissionTest.php

// Add a page that contains the shortcode that will render the signup form.
$I->havePageInDatabase( [
    'post_name' => 'signup',
    'post_content'=> 'Sign-up for our awesome thing! [signup]',
] );

// Go to the page.
$I->amOnPage( '/signup' );

// Submit the form as a user would submit it.
$I->submitForm( '#signup-form', [
  'name' => 'John Doe',
  'email' => 'john.doe@altis.com',
] );

// Make sure I see a confirmation message.
$I->waitForElement( '#signup-confirmation' );
```

or the more nuanced _CEST_ format, largerly recommended due to its DRY capabilities, eg:

```php
// SignupSubmissionCest.php

class SignupSubmissionCest {

    public function _before( FunctionalTester $I ) {
        // Add a page that contains the shortcode that will render the signup form.
        $I->havePageInDatabase( [
            'post_name' => 'signup',
            'post_content'=> 'Sign-up for our awesome thing! [signup]',
        ] );
        $I->amOnPage( '/signup' );
    }

    public function test_good_signup( FunctionalTester $I ) {
        // Submit the form as a user would submit it.
        $I->submitForm( '#signup-form', [
            'name' => 'John Doe',
            'email' => 'john.doe@altis.com',
        ] );

        // Make sure I see a confirmation message.
        $I->waitForElement( '#signup-confirmation' );
    }

    public function test_bad_email_signup( FunctionalTester $I ) {
        // Submit the form as a user would submit it.
        $I->submitForm( '#signup-form', [
            'name' => 'John Doe',
            'email' => 'not-really-an-email',
        ] );

        // Make sure I see an error message.
        $I->waitForElement( '#signup-error' );
    }
}
```

#### Functional tests

In short: *Testing a scenario from a developer perspective*, eg: sending AJAX/API requests and checking responses and/or database changes.

This type of test doesn't necessarily use a browser, as it can use a PHP library that acts like a browser, but without Javascript support. These are a lot like Acceptance tests, but serve a slightly different purpose.

Functional and acceptance tests can co-exist, eg: executing browser actions and checking expected database changes rather than just browser output.

Functional tests are typically written in _CEST_ format, eg:

```php
// SignupSubmissionCest.php

class SignupSubmissionCest {

    public function _before( FunctionalTester $I ) {
        // Add a page that contains the shortcode that will render the signup form.
        $I->havePageInDatabase( [
            'post_name' => 'signup',
            'post_content'=> 'Sign-up for our awesome thing! [signup]',
        ] );
        $I->amOnPage( '/signup' );
    }

    public function test_good_signup( FunctionalTester $I ) {
        $I->sendAjaxPostRequest( '/wp-json/acme/v1/signup', [
            '_wpnonce' => $I->grabAttributeFrom( '#signup-nonce', 'value' ),
            'name' => 'John Doe',
            'email' => 'john.doe@altis.com',
        ] );
        $I->seeResponseCodeIsSuccessful();
        $I->seeUserInDatabase( [ 'user_login' => 'john.doe', 'user_email' => 'john.doe@altis.com' ] );
    }

    public function test_bad_email_signup( FunctionalTester $I ) {
        $I->sendAjaxPostRequest( '/wp-json/acme/v1/signup', [
            '_wpnonce' => $I->grabAttributeFrom( '#signup-nonce', 'value' ),
            'name' => 'John Doe',
            'email' => 'not-really-an-email',
        ] );
        $I->seeResponseCodeIs( 400 );
        $I->dontSeeUserInDatabase( [ 'user_login' => 'john.doe', 'user_email' => 'not-really-an-email' ] );
    }
}
```

#### Integration tests

In short: *Testing code within the context of a WordPress site*, eg: testing filters and actions are behaving as expected.

This type is written in the PHPUnit format, however extending the `\Codeception\TestCase\WPTestCase` class provided by `wp-browser`, eg:

```php
// SubmissionHandlingTest.php

class SubmissionHandlingTest extends \Codeception\TestCase\WPTestCase {

    public function test_good_request() {
        $request = new WP_Rest_Request();
        $request->set_body_params( [ 'name' => 'john.doe', 'email' => 'john.doe@altis.com' ] );
        $handler = new  Acme\Signup\SubmissionHandler();
        $response = $handler->handle( $request );
        $this->assertIntsanceOf( WP_REST_Response::class, $response );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertInstanceOf( Acme\Signup\Submission_Good::class, $handler->last_submission() );
        $this->assertEquals( 'john.doe', $handler->last_submission()->name() );
        $this->assertEquals( 'john.doe@altis.com', $handler->last_submission()->email() );
    }

    public function test_bad_email_request() {
        $request = new WP_Rest_Request();
        $request->set_body_params( [ 'name' => 'john.doe', 'email' => 'not-a-valid-email' ] );
        $handler = new  Acme\Signup\SubmissionHandler();
        $response = $handler->handle( $request );
        $this->assertIntsanceOf( WP_REST_Response::class, $response );
        $this->assertEquals( 400, $response->get_status() );
        $this->assertInstanceOf( Acme\Signup\Submission_Bad::class, $handler->last_submission() );
        $this->assertEquals( 'john.doe', $handler->last_submission()->name() );
        $this->assertEquals( 'not-a-valid-email', $handler->last_submission()->email() );
    }
}
```

#### WordPress unit tests

In short: *Testing single classes or functions in as much isolation as possible*, eg: testing one class or one function that requires WordPress-defined functions or classes, with a unit testing approach.

This type is written also in PHPUnit format, extending the `\Codeception\Test\Test` class. eg:

```php
<?php
// SubmissionHandlerTest.php

class SubmissionHandlerTest extends Codeception\Test\Test {
    protected $request;
    protected $validator;

    public function setUp() {
        // Mock the request.
        $this->request = $this->prophesize( WP_REST_Request::class );
        // Spy on the validator.
        $this->validator = $this->prophesize( Acme\Signup\EmailValidator::class );
    }
    public function test_email_is_validated_by_default() {
        $this->request->get_param( 'name' )->willReturn( 'john.doe' );
        $this->request->get_param( 'email' )->willReturn( 'john.doe@altis.com' );

        $handler = new Acme\Signup\SubmissionHandler( $this->validator->reveal() );
        $handler->set_validator( $this->validator );
        $response = $handler->handle( $this->request->reveal() );
        $this->assertInstanceOf( WP_REST_Response::class, $response );

        // Verify on the validator spy.
        $this->validator->validate( 'john.doe@altis.com' )->shouldHaveBeenCalled();
    }
    public function test_will_not_validate_email_if_missing() {
        $this->request->get_param( 'name' )->willReturn( 'john.doe' );
        $this->request->get_param( 'email' )->willReturn( '' );

        $handler = new Acme\Signup\SubmissionHandler( $this->validator->reveal() );
        $handler->set_validator( $this->validator );
        $response = $handler->handle( $this->request->reveal() );
        $this->assertInstanceOf( WP_REST_Response::class, $response );

        // Verify on the validator spy.
        $this->validator->validate( Argument::any() )->shouldNotHaveBeenCalled();
    }
}
```

### Dependency Injection

Codeception has two different ways to inject Helper dependencies, or virtually any defined class:

#### a. Automated dependency injection

You can specify dependencies to inject to a test method by defining it as an argument like the following, and Codeception will take care of bootstrapping the helper and passing it as an argument:

```php
//...
function test_some_action( AcceptanceTester $I, \Helper\AdminBar $adminBar ) {}
//...
```

#### b. `_inject()`

Codeception test / actor / helper classes have a special method that lets you bootstrap helpers and virtually any PHP class that can be autoloaded. You can then attach these helpers to the test class object, which has the added benefit of being able to construct objects with arbitrary arguments:

```php
class SampleTest {

    /**
     * @var \Helper\AdminBar
     */
    protected $adminBar;

    protected function _inject( \Helper\AdminBar $adminBar ) {
        $this->adminBar = $adminBar->init( 'single-page' );
    }

    public function test_clicking_new_post( AcceptanceTester $I ) {
        $this->adminBar->clickNew( 'post' );
    }
}
```

[Read more about dependency injection on the Codeception docs](https://codeception.com/docs/07-AdvancedUsage.html#Dependency-Injection).

### Annotations

Codeception has different special annotations that help you to write tests in a more efficient way.

#### Examples

Codeception provides a similar functionality to PHPUnit's [`@dataProvider`](https://phpunit.readthedocs.io/en/9.5/writing-tests-for-phpunit.html?highlight=dataprovider#data-providers) annotations, to specify different scenarios or data sets for the same test to run once per each set of values, eg:

```php
/**
  * @example ["/api/", 200]
  * @example ["/api/protected", 401]
  * @example ["/api/not-found-url", 404]
  * @example ["/api/faulty", 500]
  */
public function test_api_responses( ApiTester $I, \Codeception\Example $example ) {
    $I->sendGet( $example[0] );
    $I->seeResponseCodeIs( $example[1] );
}
```

You can define examples in Doctrine or JSON style, eg: `@example ["/api/", 200]` or `@example { "url": "/api/", "code": 200 }` or `@example(url="/api/", code=200)`.

#### DataProviders

You can also use  PHPUnit's `@dataProvider` pattern to create _dynamic data sets_ for test methods, where the test will run once per each data set returned from the protected data provider method. The syntax differs a bit given the way test methods are written, eg:

```php
/**
* @dataProvider pageProvider
*/
public function testStaticPages( AcceptanceTester $I, \Codeception\Example $example ) {
    $I->amOnPage( $example['url'] );
    $I->see( $example['title'], 'h1' );
    $I->seeInTitle( $example['title'] );
}

/**
 * @return array
 */
protected function pageProvider() {
    return [
        [ 'url' => "/", 'title' => "Welcome" ],
        [ 'url' => "/info", 'title' => "Info" ],
        [ 'url' => "/about", 'title' => "About Us" ],
        [ 'url' => "/contact", 'title' => "Contact Us" ]
    ];
}
```

[Read more about examples and data providers on the Codeception docs](https://codeception.com/docs/07-AdvancedUsage.html#Example-Annotation).

#### Before and After

Codeception tests have special annotation types to execute methods before a certain test method, where you can define one or more prerequisites/cleanup functions, eg:

```php
protected function activate( AcceptanceTester $I ) {
    $this->loginAsAdmin();
    $this->activatePlugin( 'some-plugin' );
}

protected function cleanup( AcceptanceTester $I ) {
    $I->deactivatePlugin( 'some-plugin' );
    $I->logout();
}

/**
 * @before activate
 * @before anotherPrerequisite
 * @after cleanup
 */
public function checkPluginPageExists( AcceptanceTester $I ) {
    // ...
}
```

#### Environment

Codeception tests can be instructed to run in multiple / different environments, via the `@env` special annotation, eg:

```php
/**
 * @env chrome
 * @env firefox
 */
public function someTest() {}
```

### Available modules

Altis' Codeception integration comes bundled with the [`wp-browser` library](https://github.com/lucatume/wp-browser), which provides additional modules to simplify testing WordPress applications. Altis pre-configures these modules via the zero-config installation, so you don't need to manually configure them unless you need to override some of the default values, which you can do via test suite configuration, eg:

```yml
# acceptance.suite.yml

actor: AcceptanceTester
modules:
    enabled:
        - WPDb
        - WPBrowser
        - \Helper\Acceptance
    config:
        WPBrowser:
            headers:
                X_WPBROWSER_REQUEST: 1
```

For a list of available modules, please check the [`wp-browser` documentation on modules](https://wpbrowser.wptestkit.dev/modules), and the respective configuration options, and methods, of each.

These are the available modules from wp-browser:

#### WPBrowser

This module extends the `PHPBrowser` module, adding WordPress-specific configuration parameters and methods. It simulates a user interaction with the site without Javascript support; if you need to test your project with Javascript support use the `WPWebDriver` module instead.

[Read more on WPBrowser module configuration](https://wpbrowser.wptestkit.dev/modules/wpbrowser#configuration).

#### WPWebDriver

This module extends the `WebDriver` module adding WordPress-specific configuration parameters and methods. It simulates a user interaction with the site with Javascript support; if you don't need to test your project with Javascript support use the WPBrowser module to skip the overhead of loading a headless browser.

Altis comes with built-in browser support for Chrome, and Firefox, based on Selenium standalone Docker images, which is pre-configured to run with and be available for acceptance tests with zero-configuration required.

**Important notes:**

- During acceptance tests, two processes (or more) are working in parallel:
  - The test runner request, ie: Codeception process.
  - The browser session driven by `WPWebDriver`, ie: application process.
Both of those use different configurations and different running context / environment. And it'll save you time to distinguish between the two running processes/threads.

[Read more on WPWebDriver module configuration](https://wpbrowser.wptestkit.dev/modules/wpwebdriver#configuration).

#### WPDb

This module extends the `Db` module adding WordPress-specific configuration parameters and methods. It provides methods to read, write and update the WordPress database directly, without relying on WordPress methods, using WordPress functions or triggering WordPress filters.

Altis comes with a pre-prepared database dump that is imported on the fly to simulate a basic working site.

**Important notes:**
- `WPDb` imports the sample database content to a database called `test`, which is created (and later removed) on the fly.
- Altis detects acceptance test requests (to the actual running application) and switches the database to `test` in runtime, so it doesn't mess with existing site content.

[Read more on WPDb module configuration](https://wpbrowser.wptestkit.dev/modules/wpdb#configuration).

#### WPLoader

This module is typically used in integration tests, to bootstrap WordPress code in the context of the tests.
It can also be used in acceptance and functional tests, by setting the `loadOnly` parameter to `true`, in order to acccess WordPress code in the tests context (using the `tests` database imported by `WPDb` ).

This module is a wrapper around the functionalities provided by the [WordPress PHPUnit Core test suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/), as such it provides the same methods and facilities.

The parameters provided to the module duplicate the ones used in the WordPress configuration file. `WPLoader` will not bootstrap WordPress using the `wp-config.php` file, it will define and use its own WordPress configuration values passed from the defined module parameters.

**Important notes:**

- If the `loadOnly` parameter is set to `false`, Codeception will execute all database modification requests, eg: created and/or deleted content, as an SQL transaction, which gets rolled-back whenever the test scenario completes.
- WordPress defined functions and classes (and those of the plugins and themes loaded with it) will be available in the `setUpBeforeClass` method.
- WordPress would not have loaded yet when PHPUnit calls the data provider methods, so don't expect to be able to use any WordPress functions within data provider methods.

[Read more on WPLoader module configuration](https://wpbrowser.wptestkit.dev/modules/wploader#configuration).

#### WPQueries

This module is typically used in integration tests, to make assertions on the database queries made by the global $wpdb object, and it **requires** the `WPLoader` module in order to work. It will set, if not set already, the `SAVEQUERIES` constant to true and will throw an exception if the constant is already set to a falsy value.

[Read more on WPQueries module configuration](https://wpbrowser.wptestkit.dev/modules/wpqueries#configuration).

#### WPFilesystem

This module is typically used in acceptance and functional tests, it extends the `Filesystem` module adding WordPress-specific configuration parameters and methods. It provides methods to read, write and update the WordPress filesystem directly, without relying on WordPress methods, using WordPress functions or triggering WordPress filters.

One of the handy use cases of this module is scaffolding plugins and themes on the fly in the context of tests and automatically removing them after each test.

[Read more on WPFilesystem module configuration](https://wpbrowser.wptestkit.dev/modules/wpfilesystem#configuration).

#### WPCLI

This module is typically used in acceptance and functional tests to invoke WP-CLI commands, and test their output. It will use its own version of WP-CLI, not the one installed in the machine running the tests!

**Important notes:***
- By default, `wp-browser` will only include the `wp-cli/wp-cli` package; this package contains the basic files to run WP-CLI and does not contain all the commands that come with a typical `wp-cli` installation. If you require all the commands that usually come installed with WP-CLI, then you should require the `wp-cli/wp-cli-bundle` package as a development dependency of your project.
- This module defines the environment variable `WPBROWSER_HOST_REQUEST` to distinguish testing sessions. Altis will detect this and switch to the test database, similar to what happens in acceptance test sessions.

[Read more on WPCLI module configuration](https://wpbrowser.wptestkit.dev/modules/wpcli#configuration).

### Altis helpers

Altis extends Codeception/wp-browser with its own helpers. Check out the `tests/_helpers` directory within the `dev-tools` package to check out existing helpers and new available functionality.

## Scaffolding

Altis has a command to generate / scaffold tests and related artifacts, through the Codeception `bootstrap` and `generate` subcommands.

### Bootstrapping tests

To bootstrap the tests folder, which will create the five default tests suites:

```sh
composer dev-tools codecept bootstrap

# OR, bootstrap tests in a custom directory:
composer dev-tools codecept bootstrap -p path/to/tests

# OR, bootstrap specific test suites
composer dev-tools codecept bootstrap -p path/to/tests acceptance,unit
```

**Notes:** the `boostrap` command here is a custom implementation different from Codeception's `bootstrap` command, so it works with Altis' implementation.

### Generating tests and objects

Codeception includes a subcommand to generate different types of entities, eg: tests, helpers, environments, page objects.

```sh
composer dev-tools codecept generate:[generator] [suite] [subdir/][test-class]

# To generate a new CEST-style test in the existing `acceptance` test suite
composer dev-tools codecept generate:cest acceptance awesome-feature/admin/AwesomeFeatureAdmin

# Other generators include:


# Generates a sample Cest test
composer dev-tools codecept generate:cest suite filename

# Generates a sample PHPUnit Test with Codeception hooks
composer dev-tools codecept generate:test suite filename

# Generates Gherkin feature file
composer dev-tools codecept generate:feature suite filename

# Generates a new suite with the given Actor
composer dev-tools codecept generate:suite suite actorclass name

# Generates text files containing scenarios
composer dev-tools codecept generate:scenarios suitefrom tests

# Generates a sample Helper File
composer dev-tools codecept generate:helper filename

# Generates a sample Page object
composer dev-tools codecept generate:pageobject suite filename

# Generates a sample Step object
composer dev-tools codecept generate:stepobject suite filename

# Generates a sample Environment configuration
composer dev-tools codecept generate:environment env

# Generates a sample Group Extension
composer dev-tools codecept generate:groupobject group
```

**Note**: you'll need to specify the path to the tests folder if it's not the default root `tests` directory.
**Note**: you'll need to manually update suite configuration(s) to include the new helper/page object as needed.

## Advanced usage

### Debugging

Codeception has two ways to get more detailed output, using the `--debug` flag, and the `-v/-vv/-vvv` flags inherited from `composer`.

#### Debug statements and screenshots

Codeception allows printing debugging information, saving HTML snapshots, or saving screenshots for debugging purposes, eg:

```php
/**
  * @example ["", "Welcome"]
  * @example ["about", "About us"]
  * @example ["login", "Sign in"]
  */
public function testAwesomePages( AcceptanceTester $I, \Codeception\Example $example ) {
    # Print a debug statement.
    codecept_debug( sprintf( 'Checking page: "%s"', $example[0] );

    # Go to the page, and check its title.
    $I->amOnPage( $example[0] );
    $I->seeInTitle( $example[1] );

    # Save a page snapshot.
    $I->makeHtmlSnapshot( 'awesome-snapshot-' . $example[0] );

    # Save a screenshot of the page.
    $I->makeScreenshot( 'awesome-screenshot-' . $example[0] );

    # Save a screenshot of a specific element on the page.
    $I->makeElementScreenshot( '#header', 'awesome-screenshot-' . $example[0] );
}
```

#### Interactive console

Codeception allows real-time execution of arbitrary acceptance test code via a live browser session, so you can try out commands before writing the actual test, eg:

```sh
composer dev-tools codecept console acceptance
```

Even better, you can pause test executions programmatically and get a nice console where you can execute arbitrary commands, provided are in debug mode by supplying `--debug` flag to the `run` command ( note the need for the options delimiter `--` ), eg:

```sh
composer dev-tools codecept run acceptance -- --debug
```
then, within the test method:
```php
$I->pause();
```

**Note:** using the interactive console requires the `hoa/console` composer package, which is not installed by default. Install it via:
```sh
composer require --dev hoa/console
```

### Extensions

Codeception provides a set of useful extensions that can be used with tests, find more information about the [built-in extensions here](https://codeception.com/extensions).

To give a quick glance:

- **DotReporter**
Provides less verbose output for test execution. Like PHPUnit printer it prints dots "." for successful tests and "F" for failures.
- **Logger**
Logs suites/tests/steps using Monolog library.
- **Recorder**
Saves a screenshot of each step in acceptance tests and shows them as a slideshow on one HTML page. Usable only for suites with WebDriver module enabled.
- **RunBefore**
Executes of some processes before running tests.
- **RunFailed**
Saves failed tests into tests/_output/failed in order to rerun failed tests. Enabled by default.
- **RunProcess**
Starts and stops processes per suite. Can be used to start/stop selenium server, chromedriver, mailcatcher, etc.

### Custom config

Projects can use a custom Codeception configuration file and override Altis' zero-config setup (or select only bits and pieces as needed), by providing a custom `codeception.yml` file within the tests directory, and using the `-c` option to specify the path to it, eg:

```sh
composer dev-tools codecept run -- -c path/to/codeception.yml
```
