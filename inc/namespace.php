<?php

namespace Altis\Dev_Tools;

use const Altis\ROOT_DIR;
use function Altis\get_config;

/**
 * Bootstrap.
 */
function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\on_plugins_loaded', 1 );
}

/**
 * Load in other code as necessary.
 */
function on_plugins_loaded() {
	$config = get_config()['modules']['dev-tools'];

	if ( $config['query-monitor'] ) {
		// Hide the db.php dropin installation warning and prompt.
		add_filter( 'qm/show_extended_query_prompt', '__return_false' );
		require_once ROOT_DIR . '/vendor/johnbillion/query-monitor/query-monitor.php';
	}
}
