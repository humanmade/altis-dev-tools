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

// Ensure cache key salt is different for test runs.
defined( 'WP_CACHE_KEY_SALT' ) or define( 'WP_CACHE_KEY_SALT', 'codeception' );

// Ensure tests use their own ElasticPress indexes.
defined( 'EP_INDEX_PREFIX' ) or define( 'EP_INDEX_PREFIX', 'tests_' );

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
 * phpcs:disable HM.Functions.NamespacedFunctions.MissingNamespace
 *
 * @return void
 */
function bootstrap_codeception_wp() {
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
	 * Reindex ElasticPress on install.
	 */
	tests_add_filter( 'plugins_loaded', function () {
		global $table_prefix;

		if ( ! class_exists( 'ElasticPress\\Elasticsearch' ) ) {
			return;
		}

		// Remove the shutdown sync action to prevent errors syncing non-existent posts etc...
		foreach ( ElasticPress\Indexables::factory()->get_all() as $indexable ) {
			if ( ! isset( $indexable->sync_manager ) ) {
				continue;
			}
			remove_action( 'shutdown', [ $indexable->sync_manager, 'index_sync_queue' ] );
			remove_filter( 'wp_redirect', [ $indexable->sync_manager, 'index_sync_queue_on_redirect' ], 10, 1 );
		}

		// Ensure indexes exist before tests run and silence the output.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( sprintf(
			'TABLE_PREFIX=%s EP_INDEX_PREFIX=%s wp elasticpress index --setup --network-wide --url=%s',
			$table_prefix,
			EP_INDEX_PREFIX,
			WP_TESTS_DOMAIN
		), $output, $return_val );
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
}

// Load Altis.
require_once Altis\CODECEPTION_PROJECT_ROOT . '/wp-config.php';
