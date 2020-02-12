<?php

namespace Altis\Dev_Tools;

use const Altis\ROOT_DIR;
use function Altis\get_config;
use function Altis\get_environment_architecture;
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

	// Add Altis Config info to the Query Monitor.
	// Register collector.
	add_filter( 'qm/collectors', __NAMESPACE__ . '\\register_altis_config_qm_collector' );

	// Register outputter.
	add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\register_altis_config_qm_output_html' );
}

/**
 * Add Query Monitor collector for Altis Config info.
 *
 * @param array $collectors Array of Query Monitor collectors.
 *
 * @return array Array of Query Monitor collectors with added one for Altis Config.
 */
function register_altis_config_qm_collector( array $collectors ) : array {
	$collectors['altis-config'] = new Altis_Config\QM_Collector_Altis_Config();
	return $collectors;
}

/**
 * Add Query Monitor outputter for Altis Config info.
 *
 * @param array $output     Array of Query Monitor outputters.
 *
 * @return array Array of Query Monitor outputters with added one for Altis Config.
 */
function register_altis_config_qm_output_html( array $output ) : array {
	$collector = QM_Collectors::get( 'altis-config' );
	if ( $collector ) {
		$output['altis-config'] = new Altis_Config\QM_Output_Html_Altis_Config( $collector );
	}
	return $output;
}
