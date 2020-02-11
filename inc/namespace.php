<?php

namespace Altis\Dev_Tools;

use const Altis\ROOT_DIR;
use function Altis\get_config;
use function Altis\get_environment_architecture;

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
		// In Cloud environments, disable the Query Monitor error handler
		// as this will override our own error handler in Cloud.
		if ( in_array( get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
			define( 'QM_DISABLE_ERROR_HANDLER', true );
		}

		// Hide the db.php dropin installation warning and prompt.
		add_filter( 'qm/show_extended_query_prompt', '__return_false' );
		require_once ROOT_DIR . '/vendor/johnbillion/query-monitor/query-monitor.php';

		require_once __DIR__ . '/altis-config/collector.php';
		require_once __DIR__ . '/altis-config/output.php';
	}
}
