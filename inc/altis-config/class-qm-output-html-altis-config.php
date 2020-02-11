<?php
/**
 * Altis config data output for HTML pages.
 */

namespace Altis\Dev_Tools;

use function Altis\get_config;
use QM_Output_Html;

class QM_Output_Html_Altis_Config extends QM_Output_Html {

	public function output() {

		$config = get_config();

		if ( ! $config ) {
			return 'Something went wrong with displaying the Altis config settings.';
		}

		parent::before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html_x( 'Altis Config', 'Query monitor page item title', 'altis' ) . '</h3>';

		echo '<table>';
		echo '<tbody>';

		foreach ( $config as $module => $settings ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $module ),
				json_decode( $settings, true )
			);
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';

		parent::after_non_tabular_output();
	}
}
