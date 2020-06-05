<?php
/**
 * Altis Dev Tools.
 *
 * @package altis/dev-tools
 */

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

	// Remove "Debug Bar:" text from the Query Monitor menu item titles if any.
	// Hook on 300, as QM_Output_Html_Debug_Bar adds menus on 200.
	add_filter( 'qm/output/menus', __NAMESPACE__ . '\\cleanup_debug_bar_qm_menu_titles', 300 );
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

/**
 * Remove "Debug Bar:" text from the Query Monitor menu item titles if any.
 * Ex. "Debug Bar: <menu_title>" into "<menu_title>".
 *
 * Note, the "Debug Bar:" is a translatable string in the Query Monitor plugin,
 * so we can't just match the English spelling, as it will leave out the translated ones.
 * Instead of string replacement, access the Debug Panel Collector and use its title instead
 * as the new title for the Query Monitor menu item, thus preserving translations and
 * removing redundant text.
 *
 * @param array $menus Array of menus for the Query Monitor.
 *
 * @return array Array of menus for the Query Monitor with the cleaned up menu item titles.
 */
function cleanup_debug_bar_qm_menu_titles( array $menus ) : array {

	foreach ( $menus as $id => $menu ) {
		if ( strpos( $id, 'debug_bar_' ) === false ) {
			continue;
		}

		// Replace current menu item title (with redundant text) with just the Debug Panel title.
		// This is done to preserve translations.
		$collector_id = preg_replace( '/^qm-/', '', $id );
		$collector = QM_Collectors::get( $collector_id );
		if ( ! $collector ) {
			continue;
		}

		// Overwrite the title.
		$title = $collector->get_panel()->title();
		$menus[ $id ]['title'] = $title;
	}

	return $menus;
}
