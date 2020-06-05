<?php
/**
 * Altis Dev Tools Module.
 *
 * @package altis/dev-tools
 */

namespace Altis\Dev_Tools; // phpcs:ignore

use Altis;

// In order to configure tests properly we need to be able to ensure
// WP CLI commands can be run pointing to the correct tables.
if ( getenv( 'TABLE_PREFIX' ) ) {
	$table_prefix = getenv( 'TABLE_PREFIX' );
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled'       => Altis\get_environment_type() !== 'production',
		'query-monitor' => true,
	];

	Altis\register_module(
		'dev-tools',
		__DIR__,
		'Developer Tools',
		$default_settings,
		__NAMESPACE__ . '\\bootstrap'
	);
} );
