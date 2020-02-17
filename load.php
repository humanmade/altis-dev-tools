<?php

namespace Altis\Dev_Tools; // @codingStandardsIgnoreLine

use function Altis\get_environment_type;
use function Altis\register_module;

require_once __DIR__ . '/inc/namespace.php';

function register() {
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
}

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', __NAMESPACE__ . '\\register' );
