<?php
/**
 * Altis Dev Tools Module.
 *
 * phpcs:disable PSR1.Files.SideEffects
 *
 * @package altis/dev-tools
 */

namespace Altis\Dev_Tools; // phpcs:ignore

use Altis;

// In order to configure tests properly we need to be able to ensure
// WP CLI commands can be run pointing to the correct tables.
if ( getenv( 'TABLE_PREFIX' ) ) {
	$table_prefix = getenv( 'TABLE_PREFIX' );
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled'       => Altis\get_environment_type() !== 'production',
		'query-monitor' => true,
	];
	$options = [
		'defaults' => $default_settings,
	];
	Altis\register_module(
		'dev-tools',
		__DIR__,
		'Developer Tools',
		$options,
		__NAMESPACE__ . '\\bootstrap'
	);
} );

// Testing constants to switch to test db/redis/elastic.
add_action( 'altis.modules.init', function() {
	// Detect requests from Codeception/phpunit, primarily to use the test database.
	$is_test_request = (
		isset( $_SERVER['HTTP_X_TEST_REQUEST'] )
		|| ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'wp-browser' ) !== false )
		|| getenv( 'WPBROWSER_HOST_REQUEST' )
		// Ensure CLI commands have the test context.
		|| ( defined( 'WP_CLI' ) && WP_CLI && file_exists( Altis\ROOT_DIR . '/vendor/.test-running' ) )
		|| ( class_exists( 'HM\\Cavalcade\\Runner\\Runner' ) && file_exists( Altis\ROOT_DIR . '/vendor/.test-running' ) )
	);

	// For acceptance tests, change DB name and Elastic/Redis prefixes.
	if ( ! $is_test_request ) {
		return;
	}

	$default_host = str_replace( '/tachyon', '', getenv( 'TACHYON_URL' ) );

	define( 'WP_BROWSER_TEST', true );
	define( 'DB_NAME', 'test' );
	define( 'EP_INDEX_PREFIX', 'tests' );
	define( 'WP_CACHE_KEY_SALT', 'codecept' );
	defined( 'WP_TESTS_DOMAIN' ) || define( 'WP_TESTS_DOMAIN', $_SERVER['HTTP_HOST'] ?? $default_host );

	// Load overrides code.
	include_once( __DIR__ . '/inc/codeception/overrides.php' );

	// Support acceptance testing delayed execution. See AcceptanceTester::bootstrapWith.
	if ( file_exists( Altis\ROOT_DIR . '/vendor/webdriver-test-load.php' ) ) {
		include_once( Altis\ROOT_DIR . '/vendor/webdriver-test-load.php' );
	}
}, 1 );
