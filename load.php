<?php

namespace HM\Platform\Dev_Tools;

use HM\Platform;
use function HM\Platform\get_environment_architecture;

require_once __DIR__ . '/inc/namespace.php';

// Don't self-initialize if this is not a Platform execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'hm-platform.modules.init', function () {
	$default_settings = [
		'enabled'       => in_array( get_environment_architecture(), [ 'chassis', 'local-server' ] ),
		'query-monitor' => true,
	];

	Platform\register_module(
		'dev-tools',
		__DIR__,
		'Developer Tools',
		$default_settings,
		__NAMESPACE__ . '\\bootstrap'
	);
} );
