<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use \Codeception\Module;

class WPCLI extends Module {

	/**
	 * Previous shell output.
	 *
	 * @var string|array
	 */
	protected $shell_output;

	/**
	 * Previous shell exit code.
	 *
	 * @var int
	 */
	protected $shell_exit_code;

	/**
	 * Use the full WP CLI to run a command.
	 *
	 * @param string $command Command to run minus 'wp'.
	 * @return void
	 */
	public function wpCli( string $command = '' ) {
		unset( $this->shell_output, $this->shell_exit_code );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( sprintf(
			'WPBROWSER_HOST_REQUEST=1 wp --url=%s %s',
			getenv( 'TEST_SITE_WP_DOMAIN' ),
			$command
		), $this->shell_output, $this->shell_exit_code );
	}

	/**
	 * Use the full WP CLI to run a command and get the output.
	 *
	 * @param string $command Command to run minus 'wp'.
	 * @return string
	 */
	public function wpCliToString( string $command = '' ) : string {
		$this->wpCli( $command );
		return implode( "\n", (array) $this->shell_output );
	}

	/**
	 * Use the full WP CLI to run a command and get the output as an array.
	 *
	 * @param string $command Command to run minus 'wp'.
	 * @return array
	 */
	public function wpCliToArray( string $command = '' ) : array {
		$this->wpCli( $command );
		return (array) $this->shell_output;
	}

	/**
	 * Return the last exit code from WP CLI.
	 *
	 * @return integer
	 */
	public function grabLastWpCliExitCode() : int {
		return (int) $this->shell_exit_code;
	}

	/**
	 * Return the last output from WP CLI.
	 *
	 * @return string
	 */
	public function grabLastWpCliShellOutput() : string {
		return implode( "\n", (array) $this->shell_output );
	}
}
