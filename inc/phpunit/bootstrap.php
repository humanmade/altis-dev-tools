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

// Load custom setup file.
if ( file_exists( Altis\PHPUNIT_PROJECT_ROOT . '/.tests/setup.php' ) ) {
	require Altis\PHPUNIT_PROJECT_ROOT . '/.tests/setup.php';
}

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
