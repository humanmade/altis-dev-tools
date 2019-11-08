# Testing With PHPUnit

[PHPUnit]() is a powerful framework for testing PHP code and applications. The tests that PHPUnit excels at running can be broken down into two fundamental types:

- Unit tests
- Integration tests

Unit tests can be run against a unit of code in isolation, such as a function or object and require no persistent state.

Integration tests allow you to test a running application. In the case of Altis this type of testing is often more useful however it is possible to run both types of test.

Currently only PHPUnit <= 7.1 is supported.


## Setting Up PHPUnit

To set up PHPUnit from scratch run the following command:

```sh
composer dev-tools scaffold phpunit
```

This will create the following files:

- `phpunit.xml.dist` - Instructions for PHPUnit.
- `.tests/bootstrap.php` - Handles loading and installing Altis before running tests.
- `.tests/setup.php` - Provides access to test suite functions such as `tests_add_filter()`.
- `.tests/config.php` - A custom config file used to define constants or other code for the test run.
- `.tests/inc/class-test-sample.php` - A basic test to get you started.

If you already have PHPUnit set up for a different environment you can temporarily move your existing `phpunit.xml.dist` file and a `.tests` directory to compare the scaffolded versions to your existing ones. The dot prefix ensures this directory is not accessible to HTTP requests.

The scaffolded files can be edited as you need.


## Running Tests

With the above scaffolding in place running your unit tests is straightforward:

```sh
composer dev-tools phpunit
```

By default this will attempt to run your tests on the [Local Server](docs://local-server) environment. If you are using [Local Chassis](docs://local-chassis) add the `--chassis` flag eg:

```sh
composer dev-tools phpunit --chassis
```

### Passing Arguments To PHPUnit

Often you may have multiple test directories, different configurations to run or you want to generate coverage reports. To pass any of the supported command line options to PHPUnit you need to add them after the options delimiter `--`. For example:

```sh
composer dev-tools phpunit -- --coverage-xml coverage --log-junit junit.xml
```

The [full list of PHPUnit command line options is available here](https://phpunit.readthedocs.io/en/7.1/textui.html) or you can run `composer dev-tools phpunit -- --help`.


## Writing Tests

On top of the standard PHPUnit framework Altis bundles the WP PHPUnit framework to provide advanced functionality for integration testing.

### Naming And Organisation

All test classes should be prefixed with `class-test-` and have a `.php` suffix. The classes themselves should be namespaced and be named to match the file name, for example the file `class-test-feature-a.php` should declare a class called `Test_Feature_A`.

Within the `.tests` directory you can organise your test classes as you see fit however it is good practice to group related tests into directories. This makes it easier to run a subset of tests rather than the full suite.

Group tests into a class when they test different aspects of the same piece of functionality. It’s especially convenient to put tests together in a class when they can share a common `setUp()` or `setUpBeforeClass()` routine. As a rule, a single test class should not contain tests for more than one function/method and should test every possible input and expected output.

### Unit Tests

To create a unit test your class must extend the `PHPUnit\FrameWork\TestCase` class and the methods that contain [assertions](https://phpunit.readthedocs.io/en/7.1/assertions.html) must start with `test`.

```php
<?php

namespace Project\Tests;

use PHPUnit\Framework\TestCase;

class Test_Units extends TestCase {

	/**
	 * This special method is run once before all the tests in the class.
	 */
	public function setUpBeforeClass() {
		require_once 'path/to/file/with/functions.php';
	}

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

To run tests against the running application your tests should follow the same pattern as above but test classes should extend the `WP_UnitTestCase` class.

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
