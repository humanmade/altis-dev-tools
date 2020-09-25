<?php
/**
 * Altis PHPUnit bootstrap file.
 *
 * phpcs:disable PSR1.Files.SideEffects
 *
 * @package altis/dev-tools
 */

// Small hack to ensure docker volumes are synced before running tests.
sleep( 2 );

// Set the PHP binary to use.
define( 'WP_PHP_BINARY', '/usr/bin/env php' );

// Command is always executed from the project root on local servers.
define( 'Altis\\PHPUNIT_PROJECT_ROOT', getcwd() );

// Set tests config path.
putenv( sprintf(
	'WP_PHPUNIT__TESTS_CONFIG=%s/vendor/altis/dev-tools/inc/phpunit/config.php',
	Altis\PHPUNIT_PROJECT_ROOT
) );

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available.
require_once Altis\PHPUNIT_PROJECT_ROOT . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Re-map the default `/uploads` folder with our own `/test-uploads` for tests.
 *
 * WordPress core runs a method (scan_user_uploads) on the first instance of `WP_UnitTestCase`.
 * This method scans every single folder and file in the uploads directory. This is a problem
 * if the regular uploads directory contains a lot of files.
 *
 * This filter adds a unique test uploads folder just for our tests to reduce load.
 */
tests_add_filter( 'upload_dir', function( $dir ) {
	array_walk( $dir, function( &$item ) {
		if ( is_string( $item ) ) {
			$item = str_replace( '/uploads', '/test-uploads', $item );
		}
	} );
	return $dir;
} );

/**
 * Setup ElasticPress on install.
 */
define( 'EP_INDEX_PREFIX', 'tests_' );
tests_add_filter( 'plugins_loaded', function () {
	global $table_prefix;

	if ( ! function_exists( 'ep_index_exists' ) ) {
		return;
	}

	// Elevate error reporting level.
	$error_reporting_level = error_reporting();
	error_reporting( E_ERROR );

	if ( ep_index_exists() ) {
		return;
	}

	exec( sprintf(
		'TABLE_PREFIX=%s EP_INDEX_PREFIX=%s wp elasticpress index --setup --network-wide --url=%s',
		$table_prefix,
		EP_INDEX_PREFIX,
		WP_TESTS_DOMAIN
	) );

	error_reporting( $error_reporting_level );
}, 11 );

/**
 * Ensure Stream is installed.
 */
define( 'WP_STREAM_DEV_DEBUG', true );
tests_add_filter( 'plugins_loaded', function () {
	if ( function_exists( 'wp_stream_get_instance' ) && wp_stream_get_instance()->install ) {
		wp_stream_get_instance()->install->check();
	}
}, 21 );

/**
 * Modify the cache keys to prevent conflicts.
 */
define( 'WP_CACHE_KEY_SALT', 'phpunit' );

// Load custom bootstrap code.
if ( file_exists( Altis\PHPUNIT_PROJECT_ROOT . '/.config/tests-bootstrap.php' ) ) {
	require Altis\PHPUNIT_PROJECT_ROOT . '/.config/tests-bootstrap.php';
}

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';

/**
 * Action runs after WP PHPUnit is fully loaded. Use this to load any
 * custom test case classes that extend WP_UnitTestCase.
 */
do_action( 'altis.loaded_phpunit' );
