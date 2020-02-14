<?php
/**
 * Altis Config data output for HTML pages in the Query Monitor plugin.
 */

namespace Altis\Dev_Tools\Altis_Config;

use function Altis\get_config;
use function Altis\get_environment_type;
use QM_Collector;
use QM_Output_Html;

class QM_Output_Html_Altis_Config extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', [ $this, 'admin_menu' ] );
	}

	public function output() {
		$config = get_config()['modules'] ?? [];

		if ( ! $config ) {
			echo esc_html_x( 'Something went wrong with displaying the Altis config settings.', 'Displaying Altis config info in the Query Monitor plugin', 'altis' );
			return;
		}
		?>

		<?php $this->before_tabular_output(); ?>

		<thead>
			<tr>
				<th><?php echo esc_html__( 'Module', 'altis' ); ?></th>
				<th><?php echo esc_html__( 'Merged Settings', 'altis' ); ?></th>
				<th><?php echo esc_html__( 'Environment Settings', 'altis' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $config as $module => $settings ) : ?>
			<tr>
				<td><?php echo esc_html( $module ); ?></td>
				<td>
					<?php
					printf(
						'<pre>%s</pre>',
						esc_html( json_encode( (object) $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
					);
					?>
				</td>
				<td>
					<?php
					// Environment specific settings for the current module.
					$env_settings = get_config()['environments'][ get_environment_type() ]['modules'][ $module ] ?? [];
					printf(
						'<pre>%s</pre>',
						esc_html( json_encode( (object) $env_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
					);
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>

		<?php
		$this->after_tabular_output();
	}
}
