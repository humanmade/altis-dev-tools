<?php
/**
 * Altis PHPUnit bootstrap file.
 *
 * phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols
 */

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

// Load custom bootstrap code.
if ( file_exists( Altis\PHPUNIT_PROJECT_ROOT . '/.config/tests-bootstrap.php' ) ) {
	require Altis\PHPUNIT_PROJECT_ROOT . '/.config/tests-bootstrap.php';
}

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
