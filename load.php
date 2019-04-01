<?php

namespace HM\Platform\Dev_Tools;

use HM\Platform;
use function HM\Platform\get_environment_architecture;

require_once __DIR__ . '/inc/namespace.php';

add_action( 'hm-platform.modules.init', function () {
	$default_settings = [
		'enabled'       => in_array( get_environment_architecture(), [ 'local-server' ] ),
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
