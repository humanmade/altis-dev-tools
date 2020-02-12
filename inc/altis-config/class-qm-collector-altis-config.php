<?php

namespace Altis\Dev_Tools;

use QM_Collector;

class QM_Collector_Altis_Config extends QM_Collector {

	public $id = 'altis-config';

	function name() {
		return _x( 'Altis Config', 'Menu item name for the Query Monitor plugin', 'altis' );
	}
}
