<?php

namespace Altis\Dev_Tools; // @codingStandardsIgnoreLine

use function Altis\get_environment_type;
use function Altis\register_module;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled'       => get_environment_type() !== 'production',
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
