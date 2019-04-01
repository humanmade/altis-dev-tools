<?php

namespace HM\Platform\Dev_Tools;

use const HM\Platform\ROOT_DIR;
use function HM\Platform\get_config;

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
		require_once ROOT_DIR . '/vendor/johnbillion/query-monitor/query-monitor.php';
	}
}
