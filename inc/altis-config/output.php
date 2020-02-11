<?php
/**
 * Altis config data output for HTML pages.
 */

//namespace Altis\Dev_Tools;

//use QM_Output_Html;

class QM_Output_Html_Altis_Config extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Output_Html_Altis_Config Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 115 );
	}

	public function name() {
		return _x( 'Altis Config', 'Query monitor menu item name', 'altis' );
	}

	public function output() {

		$data = $this->collector->get_data();

		if ( ! isset( $data['altis-config'] ) ) {
			return 'Something went wrong with displaying the Altis config settings.';
		}

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html_x( 'Altis Config', 'Query monitor page item title', 'altis' ) . '</h3>';

		echo '<table>';
		echo '<tbody>';

		foreach ( $data['altis-config'] as $module => $settings ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $module ),
				json_decode( $settings, true )
			);
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';

		$this->after_non_tabular_output();
	}
}

/**
 * @param array         $output
 * @param QM_Collectors $collectors
 *
 * @return array
 */
function register_qm_output_html_altis_config( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'altis-config' );
	if ( $collector ) {
		$output['altis-config'] = new QM_Output_Html_Altis_Config( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html',  'register_qm_output_html_altis_config', 120, 2 );
