<?php

namespace Altis\Dev_Tools;

use const Altis\ROOT_DIR;
use function Altis\get_config;
use function Altis\get_environment_architecture;
use QM_Collector;
use QM_Collectors;

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

	if ( ! $config['query-monitor'] ) {
		return;
	}

	// In Cloud environments, disable the Query Monitor error handler
	// as this will override our own error handler in Cloud.
	if ( in_array( get_environment_architecture(), [ 'ec2', 'ecs' ], true ) ) {
		define( 'QM_DISABLE_ERROR_HANDLER', true );
	}

	// Hide the db.php dropin installation warning and prompt.
	add_filter( 'qm/show_extended_query_prompt', '__return_false' );
	require_once ROOT_DIR . '/vendor/johnbillion/query-monitor/query-monitor.php';

	// Add Altis config info to Query Monitor.
	add_altis_config_info_to_qm();
}

function add_altis_config_info_to_qm() {

	// Register collector.
	require_once __DIR__ . '/altis-config/collector.php';
	QM_Collectors::add( new QM_Collector_Altis_Config() );

	// Register outputter.
	add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\register_qm_output_html_altis_config', 120, 2 );
}

/**
 * @param array         $output
 * @param QM_Collectors $collectors
 *
 * @return array
 */
function register_qm_output_html_altis_config( array $output, QM_Collectors $collectors ) {
	require_once __DIR__ . '/altis-config/output.php';

	$collector = QM_Collectors::get( 'altis-config' );
	if ( $collector ) {
		$output['altis-config'] = new QM_Output_Html_Altis_Config( $collector );
	}
	return $output;
}