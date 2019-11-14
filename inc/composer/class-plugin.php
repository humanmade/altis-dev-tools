<?php

namespace Altis\Dev_Tools\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable {
	public function activate( Composer $composer, IOInterface $io ) {
		$this->composer = $composer;
	}

	public function getCapabilities() {
		return [
			'Composer\\Plugin\\Capability\\CommandProvider' => __NAMESPACE__ . '\\Command_Provider',
		];
	}

	/**
	 * Register the composer events we want to run on.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() : array {
		return [
			'post-autoload-dump' => [ 'install_files' ],
		];
	}

	public function install_files() {
		$source = dirname( __DIR__, 2 );
		$dest   = dirname( $this->composer->getConfig()->get( 'vendor-dir' ) );

		// Create .travis.yml if one doesn't exist yet.
		if ( ! file_exists( $dest . '/.travis.yml' ) ) {
			copy( $source . '/travis/boilerplate.yml', $dest . '/.travis.yml' );
			return;
		}

		// Check files match.
		$source_hash = md5( file_get_contents( $source . '/travis/boilerplate.yml' ) );
		$dest_hash = md5( file_get_contents( $dest . '/.travis.yml' ) );

		if ( $source_hash === $dest_hash ) {
			return;
		}

		// Files are mismatched, show a warning.
		trigger_error(
			'The file .travis.yml does not match that required by Altis.' . "\n" .
			'Follow the guide at https://www.altis-dxp.com/resources/docs/dev-tools/continuous-integration/ for help migrating.',
			E_USER_WARNING
		);
	}
}
