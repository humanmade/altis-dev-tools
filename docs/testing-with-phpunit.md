# Testing With PHPUnit

[PHPUnit](https://phpunit.de) is a powerful framework for testing PHP code and applications. The tests that PHPUnit excels at running can be broken down into two fundamental types:

- Unit tests
- Integration tests

Unit tests can be run against a unit of code in isolation, such as a function or object and require no persistent state.

Integration tests allow you to test a running application. In the case of Altis this type of testing is often more useful however it is possible to run both types of test.

**NOTE**: Currently only [PHPUnit 7.1](https://phpunit.readthedocs.io/en/7.1/) is supported.


## Zero Configuration

PHPUnit in Altis requires zero configuration for the following scenario:

- Tests are in a directory called `tests` in the project root
- Test class file names match one of the following patterns:
  - `class-test-*.php`
  - `test-*.php`
  - `*-test.php`

### Configuration Options

While the zero configuration option is sufficient for most projects there may be occasions where you wish to include additional directories, change or add attributes on the `<phpunit>` tag or add PHPUnit extensions. This is supported through the Dev Tools module config. For example:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"dev-tools": {
					"phpunit": {
						"directories": [
							"content/mu-plugins/namespace-*/tests",
							".tests"
						],
						"attributes": {
							"colors": "false",
							"beStrictAboutChangesToGlobalState": "true"
						},
						"extensions": [
							"CustomPHPUnitExtension\\Class"
						]
					}
				}
			}
		}
	}
}
```

- `directories` is an array of project root relative paths or glob patterns.
- `attributes` is an object of [name-value pairs supported by the `<phpunit>` tag](https://phpunit.readthedocs.io/en/7.1/configuration.html#phpunit)
- `extensions` is an array of [PHPUnit Extension](https://phpunit.readthedocs.io/en/7.1/extending-phpunit.html) class names to autoload


## Running Tests

To run PHPUnit tests run the following command:

```sh
composer dev-tools phpunit
```

By default this will attempt to run your tests on the [Local Server](docs://local-server) environment. If you are using [Local Chassis](docs://local-chassis) add the `--chassis` flag eg:

```sh
composer dev-tools phpunit --chassis
```

### Passing Arguments To PHPUnit

To pass any of the supported command line options to PHPUnit you need to add them after the options delimiter `--`. For example:

```sh
# Running tests in a specific directory.
composer dev-tools phpunit -- content/themes/custom-theme/tests

# Running tests with code coverage and junit reports.
composer dev-tools phpunit -- --coverage-xml coverage --log-junit junit.xml
```

The [full list of PHPUnit command line options is available here](https://phpunit.readthedocs.io/en/7.1/textui.html) or you can run `composer dev-tools phpunit -- --help`.


## Writing Tests

Group tests into a class when they test different aspects of the same piece of functionality. It’s especially convenient to put tests together in a class when they can share a common `setUp()` or `setUpBeforeClass()` routine. As a rule, a single test class should not contain tests for more than one function/method and should test every possible input and expected output.

### Unit Tests

To create a unit test your class must extend the `PHPUnit\FrameWork\TestCase` class and the methods that contain [assertions](https://phpunit.readthedocs.io/en/7.1/assertions.html) must start with `test`.

```php
<?php

namespace Project\Tests;

use PHPUnit\Framework\TestCase;

class Test_Units extends TestCase {

	public function test_complex_maths_function() {
		// Run the function to test.
		$value = complex_maths( 22.24, 87 );
		// Make sure the value is what we expect.
		$this->assertSame( $value, 7 );
	}

}
```

The framework has a great many features, it is highly recommended to [read through the PHPUnit documentation](https://phpunit.readthedocs.io/en/7.1/writing-tests-for-phpunit.html) to get the most value out of it.

### Integration Tests

Altis also bundles the WordPress PHP Testing Framework, which is an extension of the PHPUnit testing framework designed to enable running WordPress integration tests.

To run tests against the running application your tests should follow the same pattern as above but test classes should extend the `WP_UnitTestCase` class instead.

```php
<?php

namespace Project\Tests;

use WP_UnitTestCase;

class Test_Custom_Post_Type extends WP_UnitTestCase {

	public function test_post_type_exists() {
		// The full application and it's functions are loaded.
		$this->assertTrue( post_type_exists( 'event' ) );
	}

}
```

The `WP_UnitTestCase` class provides a factory object for creating content, users and more. This factory object handles resetting data so you can be confident individual tests are running in a consistent environment.

The following example demonstrates adding a user to be persisted across all tests and a test that creates a post with that user as the author. The user will exist for all tests in the class while the post created within the test method will be cleaned up immediately after that test.

```php
<?php

namespace Project\Tests;

use WP_UnitTestCase;

class Test_Custom_Post_Type extends WP_UnitTestCase {

	/**
	 * Store the user ID.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Runs before the entire class and recieves the WP factory object.
	 */
	public function wpSetUpBeforeClass( $factory ) {
		// Create a user object.
		self::$user_id = $factory->user->create( [
			'role' => 'editor',
		] );
	}

	/**
	 * Clean up objects created in wpSetUpBeforeClass.
	 */
	public function wpTearDownAfterClass() {
		wp_delete_user( self::$user_id );
	}

	public function test_post_has_default_meta_data() {
		// Use self::factory() to retrieve the factory object outside of the
		// wpSetUpBeforeClass and wpTearDownAfterClass methods.
		$post_id = self::factory()->post->create( [
			'post_type' => 'event',
			'post_author' => self::$user_id,
		] );

		$event_date = get_post_meta( $post_id, 'event_date', true );
		$post = get_post( $post_id );

		$this->assertEquals( $event_date, $post->post_date );
	}

}
```

#### The Factory Object

The factory object has the following signature:

```php
$factory->$object_type->$action();
```

`$object_type` can be one of:

- `post`
- `user`
- `attachment`
- `comment`
- `term`
- `category`
- `tag`
- `blog`
- `network`

`$action` can be any one of:

- `create( array $args, [ array $generation_definitions ] )`<br />
  Creates the object and returns the object ID.
- `create_and_get( array $args, [ array $generation_definitions ] )`<br />
  Creates and returns the object itself.
- `create_many( int $count, array $args, [ array $generation_definitions ] )`<br />
  Creates a number of the target object type and returns an array of IDs.
- `create_upload_object( string $file, int $parent = 0 )`<br />
  For the attachment type only, this will upload a file specified by the path in `$file`.

The `$args` array for each action is processed and passed to core functions in the following way:

|Object Type|Core Function Call|
|-|-|
|`post`|`wp_insert_post( array $args )`|
|`user`|`wp_insert_user( array $args )`|
|`attachment`|`wp_insert_attachment( array $args, string $args['file'], int $args['post_parent'] )`|
|`comment`|`wp_insert_comment( array $args )`|
|`term`|`wp_insert_term( string $args['name'], string $args['taxonomy'], array $args )`|
|`category`|`wp_insert_term( string $args['name'], 'category', array $args )`|
|`tag`|`wp_insert_term( string $args['name'], 'post_tag', array $args )`|
|`blog`|`wpmu_create_blog( $args['domain'], $args['path'], $args['title'], $args['user_id'], $args['meta'], $args['site_id'] )`|
|`network`|`populate_network( $args['network_id'], $args['domain'], WP_TESTS_EMAIL, $args['title'], $args['path'], $args['subdomain_install'] )`|

Generation definitions allow you to control the default values for keys in the `$args` array passed to factory methods. For example to set default values when creating a set of posts you could do the following:

```php
$post_ids = self::factory()->post->create_many( 10, [], [
	'post_title' => new WP_UnitTest_Generator_Sequence( 'Custom Post Number %s' ),
	'post_content' => 'Lorem ipsum dolor sit amet...',
] );
```

The `WP_UnitTest_Generator_Sequence` class will replace any `%s` placeholders with the current iteration number.

### Extending The Bootstrap Process

The default bootstrap process loads Composer's `autoload.php` file and Altis itself. Depending on your project you may need to run some custom code very early in the process to make sure everything is properly loaded and configured if it can't be handled through standard Altis configuration.

Add a file called `tests-bootstrap.php` to your root `.config` directory and it will be automatically included. From there you can call the `tests_add_filter()` helper function which is a way to use WordPress hooks before the application is loaded.

The following example manually sets the theme to use when running tests:

```php
// Hook in early to muplugins_loaded.
tests_add_filter( 'muplugins_loaded', function () {
	// Get the target theme directory and theme name.
	$theme_dir = dirname( dirname( __FILE__ ) ) . '/content/themes/custom-theme';
	$theme = basename( $theme_dir );

	// Register the theme.
	register_theme_directory( dirname( $theme_dir ) );

	// Force the theme to always be used for tests.
	add_filter( 'pre_option_template', function() use ( $theme ) {
		return $theme;
	} );
	add_filter( 'pre_option_stylesheet', function() use ( $theme ) {
		return $theme;
	} );
} );
```

If you need to add any custom configuration such as constants for Altis you can use the `altis.loaded_autoloader` hook:

```php
tests_add_filter( 'altis.loaded_autoloader', function () {
	define( 'TEST_ONLY_CONSTANT', true );
}, 0 );
```

### Extending `WP_UnitTestCase` Classes

In some cases you may wish to avoid repetitive code or add common helper methods to the standard `WP_UnitTestCase` class. Because the `WP_UnitTestCase` classes are loaded after the main bootstrap process you need to use the `altis.loaded_phpunit` action hook to ensure they're available.

In your `.config/tests-bootstrap.php` you would add:

```php
tests_add_filter( 'altis.loaded_phpunit', function () {
	// Load custom test case classes here.
	require_once dirname( __DIR__ ) . '/tests/class-custom-unit-test-case.php';
} );
```

In `tests/class-custom-unit-test-case.php` you could then add something like the following:

```php
<?php
class Custom_UnitTestCase extends WP_UnitTestCase {

	public function wpSetUpBeforeClass( $factory ) {
		// Common set up routine.
	}

	public function wpTearDownAfterClass( $factory ) {
		// Common tear down routine.
	}

}
```

## Using A Custom Configuration File

In order to run PHPUnit with your own XML config file you can pass the `--configuration` option like so:

```sh
composer dev-tools phpunit -- --configuration phpunit.xml
```

If you wish to retain the benefits of the built in bootstrap process your basic config file should look something like this:

```xml
<?xml version="1.0"?>
<phpunit
	bootstrap="vendor/altis/dev-tools/inc/phpunit/bootstrap.php"
	backupGlobals="false"
	colors="true"
	convertErrorsToExceptions="true"
	convertNoticesToExceptions="true"
	convertWarningsToExceptions="true"
	>
	<testsuites>
		<testsuite name="project">
			<directory prefix="class-test-" suffix=".php">tests</directory>
			<directory prefix="class-test-" suffix=".php">content/mu-plugins/*/tests</directory>
		</testsuite>
	</testsuites>
</phpunit>
```
