<?php

namespace Altis\Dev_Tools;

use function Altis\get_environment_architecture;
use function Altis\register_module;

require_once __DIR__ . '/inc/namespace.php';

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled'       => in_array( get_environment_architecture(), [ 'chassis', 'local-server' ] ),
		'query-monitor' => true,
	];

	register_module(
		'dev-tools',
		__DIR__,
		'Developer Tools',
		$default_settings,
		__NAMESPACE__ . '\\bootstrap'
	);
} );
