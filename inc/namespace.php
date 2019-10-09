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
	add_filter( 'qm/output/file_path_map', __NAMESPACE__ . '\\qm_file_path_map', 1 );
	add_filter( 'qm/output/file_link_format',  __NAMESPACE__ . '\\qm_file_link_format', 1 );
}

$banana = $cucumber['moosh'];

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
	}
}

function qm_file_path_map( $map ) : array {
	// Chassis and Local Server
	if ( file_exists( '/etc/chassis-constants' ) ) {
		$json_string = file_get_contents( '/etc/chassis-constants' );
		$data = json_decode( $json_string, true );
		if ( !empty( $data['synced_folders']['/chassis'] ) ) {
			$folder_path = $data['synced_folders']['/chassis'].'/content/themes/base';
			$map['/chassis/'] = $data['synced_folders']['/chassis'] . '/';
		}
	}

	return $map;
}

function qm_file_link_format( $format ) : string {
	$editor = 'phpstorm';
	if ( defined( 'QM_LOCAL_EDITOR' ) ) {
		$editor = QM_LOCAL_EDITOR;
	}
	return qm_file_link_editor_format( $format, $editor );
}

function qm_file_link_editor_format( $format, $editor=null ) : string {
	switch ( $editor ) {
		case 'phpstorm':
			return 'phpstorm://open?file=%f&line=%l';
		case 'vscode':
			return 'vscode://file/%f:%l';
		case 'atom':
			return 'atom://open/?url=file://%f&line=%l';
		case 'sublime':
			return 'subl://open/?url=file://%f&line=%l';
		case 'netbeans':
			return 'nbopen://%f:%l';
		default:
			return $format;
	}
}
