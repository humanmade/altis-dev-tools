<?php
/**
 * Bootstrapping code for codeception tests
 *
 * This runs in Codeception test threads.
 */

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv

// Load generic overrides code.
include __DIR__ . '/overrides.php';

// Load custom bootstrap code.
if ( file_exists( Altis\CODECEPTION_PROJECT_ROOT . '/.config/tests-bootstrap.php' ) ) {
	require Altis\CODECEPTION_PROJECT_ROOT . '/.config/tests-bootstrap.php';
}

// Load an escape hatch early load file, if it exists.
if ( is_readable( Altis\CODECEPTION_PROJECT_ROOT . '/.config/load-early.php' ) ) {
	include_once Altis\CODECEPTION_PROJECT_ROOT . '/.config/load-early.php';
}

// Ensure wp-settings.php is bypassed and allow the wp loader bootstrap.php to do it.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __FILE__ );

// Load Altis but not WP.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/wp-config.php';

/**
 * Action runs after WP PHPUnit is fully loaded. Use this to load any
 * custom test case classes that extend WP_UnitTestCase.
 */
do_action( 'altis.loaded_phpunit' );
