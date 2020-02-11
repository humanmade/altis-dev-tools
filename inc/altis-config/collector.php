<?php

//namespace Altis\Dev_Tools;

use function Altis\get_config;
//use QM_Collector;

class QM_Collector_Altis_Config extends QM_Collector {

	public $id = 'altis-config';

	public function __construct() {
		parent::__construct();

		$this->data['altis-config'] = get_config();
	}
}

/**
 * @param array        $collectors
 * @param QueryMonitor $qm
 *
 * @return array
 */
function register_qm_collector_altis_config( array $collectors, QueryMonitor $qm ) {
	$collectors['altis_config'] = new QM_Collector_Altis_Config();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_altis_config', 20, 2 );
