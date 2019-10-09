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
	add_filter( 'qm/output/file_link_format', __NAMESPACE__ . '\\qm_file_link_format', 1 );
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
	}
}

/**
 * Implements a Query Monitor filter to adjust the
 * URLs used in stack traces for editor support.
 *
 * @param string $format a protocol URL format
 * @return string a protocol URL format
 */
function qm_file_link_format( $format ) : string {
	$editor = null;
	if ( defined( 'QM_LOCAL_EDITOR' ) ) {
		$editor = QM_LOCAL_EDITOR;
	}
	return qm_file_link_editor_format( $format, $editor );
}

/**
 * Provides a protocol URL for edit links in QM stack
 * traces for various editors.
 *
 * @param string $default_format a format to use if no editor is found
 * @param string|null $editor the chosen code editor
 * @return string a protocol URL format
 */
function qm_file_link_editor_format( $default_format, $editor = null ) : string {
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
			return $default_format;
	}
}
