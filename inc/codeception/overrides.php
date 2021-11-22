<?php
/**
 * Bootstrapping code for codeception tests
 *
 * This runs both on all Codeception test requests, and acceptance test requests as well.
 */

// phpcs:disable PSR1.Files.SideEffects

// Register shutdown event to clear the cache, also triggers if an error occurs.
register_shutdown_function( function () {
	if ( ( ! defined( 'WP_BROWSER_TEST' ) || ! WP_BROWSER_TEST ) && function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
} );

// If running from the acceptance test thread, ie: in a full WP environment.
if ( ! function_exists( 'tests_add_filter' ) ) {
	/**
	 * Polyfill the tests_add_filter on acceptance tests request threads for consistency.
	 *
	 * phpcs:disable HM.Functions.NamespacedFunctions
	 *
	 * @return mixed
	 */
	function tests_add_filter() {
		return add_filter( ...func_get_args() );
	}
}

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

	// Track index creation.
	$index_exists = true;

	foreach ( ElasticPress\Indexables::factory()->get_all() as $indexable ) {
		if ( ! $indexable->index_exists() ) {
			$index_exists = false;
		}

		// Remove the shutdown sync action to prevent errors syncing non-existent posts etc...
		if ( ( defined( 'WP_BROWSER_TEST' ) && WP_BROWSER_TEST ) || ! isset( $indexable->sync_manager ) ) {
			continue;
		}
		remove_action( 'shutdown', [ $indexable->sync_manager, 'index_sync_queue' ] );
		remove_filter( 'wp_redirect', [ $indexable->sync_manager, 'index_sync_queue_on_redirect' ], 10, 1 );
	}

	// Only index if we haven't already.
	if ( $index_exists ) {
		return;
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
