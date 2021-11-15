<?php
/**
 * Altis Tests Config file.
 *
 * phpcs:disable PSR1.Files.SideEffects
 *
 * @package altis/dev-tools
 */

// Command is always executed from the project root on local servers.
if ( ! defined( 'Altis\\CODECEPTION_PROJECT_ROOT' ) ) {
	define( 'Altis\\CODECEPTION_PROJECT_ROOT', getcwd() );
}

defined( 'WP_TESTS_DOMAIN' ) or define( 'WP_TESTS_DOMAIN', getenv( 'TEST_SITE_WP_DOMAIN' ) );
defined( 'WP_TESTS_EMAIL' ) or define( 'WP_TESTS_EMAIL', getenv( 'TEST_SITE_ADMIN_EMAIL' ) );
defined( 'WP_TESTS_TITLE' ) or define( 'WP_TESTS_TITLE', 'Test Blog' );

// Set tests table prefix.
$table_prefix = 'wp_';

// Register shutdown event to clear the cache, also triggers if an error occurs.
register_shutdown_function( function () {
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
} );

/**
 * Bootstrapping test code and loading of Altis
 *
 * This is passed to wp-browser's WPLoader module config in codeception.yml
 *
 * phpcs:disable HM.Functions.NamespacedFunctions.MissingNamespace
 *
 * @return void
 */
function bootstrap_codeception_wp() {
	include __DIR__ . '/bootstrap.php';
}

// Load Altis.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/wp-config.php';
