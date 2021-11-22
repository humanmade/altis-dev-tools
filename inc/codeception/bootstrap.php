<?php
/**
 * Bootstrapping code for codeception tests
 *
 * This runs in Codeception test threads.
 */

// phpcs:disable PSR1.Files.SideEffects

// Load generic overrides code.
include __DIR__ . '/overrides.php';

// Load custom bootstrap code.
if ( file_exists( Altis\CODECEPTION_PROJECT_ROOT . '/.config/tests-bootstrap.php' ) ) {
	require Altis\CODECEPTION_PROJECT_ROOT . '/.config/tests-bootstrap.php';
}

// Load an escape hatch early load file, if it exists.
if ( is_readable( Altis\CODECEPTION_PROJECT_ROOT . '/.config/load-early.php' ) ) {
	include_once __DIR__ . '/.config/load-early.php';
}

// Load the plugin API (like add_action etc) early, so everything loaded
// via the Composer autoloaders can using actions.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/wordpress/wp-includes/plugin.php';

// Load the whole autoloader very early, this will also include
// all `autoload.files` from all modules.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/vendor/autoload.php';

// Load all modules.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/vendor/modules.php';

do_action( 'altis.loaded_autoloader' );

/**
 * Action runs after WP PHPUnit is fully loaded. Use this to load any
 * custom test case classes that extend WP_UnitTestCase.
 */
do_action( 'altis.loaded_phpunit' );
