<?php
/**
 * Altis Config data collector for the Query Monitor plugin.
 */

namespace Altis\Dev_Tools\Altis_Config;

use QM_Collector;

class QM_Collector_Altis_Config extends QM_Collector {

	public $id = 'altis-config';

	function name() {
		return esc_html_x( 'Altis Config', 'Menu item name for the Query Monitor plugin', 'altis' );
	}
}
