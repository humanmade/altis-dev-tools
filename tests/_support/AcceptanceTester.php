<?php

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
	use _generated\AcceptanceTesterActions;

	/**
	 * Custom actions here
	 */

	/**
	 * Execute a callback during remote acceptance test executions.
	 *
	 * @param callable $function Reference to a static class method.
	 * @param string $file Optional file path, auto detected from method class otherwise.
	 * @param bool $rollback_on_shutdown Rollback changes on shutdown.
	 *
	 * @throws Exception Throws an exception if passed parameters are not as expected.
	 *
	 * @return callable
	 */
	public function bootstrapWith( callable $function, string $file = null, bool $rollback_on_shutdown = true ) {
		$path = \Codeception\Configuration::projectDir() . 'webdriver-test-load.php';

		if ( ! is_array( $function ) || ! is_string( $function[0] ) ) {
			throw new Exception( 'Passed function needs to be a static method of a class.' );
		}

		if ( is_null( $file ) ) {
			$class = $function[0];
			$reflector = new \ReflectionClass( $class );
			$file = $reflector->getFileName();
		}

		if ( ! file_exists( $file ) ) {
			throw new Exception( 'File path does not exist.' );
		}

		$content = sprintf( '<?php require( "%s" ); call_user_func( [ "%s", "%s" ] );', $file, $function[0], $function[1] );
		file_put_contents( $path, $content );

		$rollback_callback = function() use ( $path ) {
			@unlink( $path );
		};

		if ( $rollback_on_shutdown ) {
			register_shutdown_function( $rollback_callback );
		}

		return $rollback_callback;
	}

	/**
	 * Run ElasticPress indexing command.
	 *
	 * WPBrowser DB management commands to not trigger WordPress actions, and hence Elastic does not keep track
	 * of content updates and index them. This command makes sure Elastic index is updated to reflect the changes.
	 *
	 * @param string $options Additional arguments to append to the indexing command.
	 * @return void
	 */
	public function reindexElastic( string $options = '' ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( sprintf(
			'WPBROWSER_HOST_REQUEST=1 wp elasticpress index --network-wide --setup --yes %s',
			$options
		), $output );
	}
}
