<?php

namespace Altis\Dev_Tools\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Chassis command for Composer.
 */
class Command extends BaseCommand {
	/**
	 * Configure the command.
	 */
	protected function configure() {
		$this->setName( 'dev-tools' );
		$this->setDescription( 'Developer tools' );
		$this->setDefinition( [
			new InputArgument( 'subcommand', InputArgument::REQUIRED, 'phpunit' ),
			new InputOption( 'chassis', null, null, 'Run commands in the Local Chassis environment' ),
			new InputArgument( 'options', InputArgument::IS_ARRAY ),
		] );
		$this->setHelp(
			<<<EOT
Run a dev tools feature.

To run PHPUnit integration tests:
    phpunit [--chassis] [--] [options]
                                use `--` to separate arguments you want to
                                pass to phpunit. Use the --chassis option
                                if you are running Local Chassis.
EOT
		);
	}

	/**
	 * Wrapper command to dispatch subcommands
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int Status code to return
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$subcommand = $input->getArgument( 'subcommand' );
		switch ( $subcommand ) {
			case 'phpunit':
				return $this->phpunit( $input, $output );

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $subcommand ) );
		}
	}

	/**
	 * Runs PHPUnit with zero config by default.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function phpunit( InputInterface $input, OutputInterface $output ) {
		$options = [];

		// Check for passed config option.
		$input_options = implode( ' ', $input->getArgument( 'options' ) );
		if ( ! preg_match( '/(-c|--configuration)\s+/', $input_options ) ) {
			$options[] = '-c vendor/altis/dev-tools/inc/phpunit/phpunit.xml';
		}

		return $this->run_command( $input, $output, 'vendor/bin/phpunit', $options );
	}

	/**
	 * Run the passed command on either the local-server or local-chassis environment.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $command The command to run.
	 * @param array $options Any required options to pass to the command.
	 * @return void
	 */
	protected function run_command( InputInterface $input, OutputInterface $output, string $command, array $options = [] ) {
		$use_chassis = $input->getOption( 'chassis' );
		$cli = $this->getApplication()->find( $use_chassis ? 'chassis' : 'local-server' );
		$input_options = $input->getArgument( 'options' );

		// Add the command, default options and input options together.
		$options = array_merge(
			[ $command ],
			$options,
			$input_options
		);

		$return_val = $cli->run( new ArrayInput( [
			'subcommand' => 'exec',
			'options' => $options,
		] ), $output );

		return $return_val;
	}

	/**
	 * Get the root directory path for the project.
	 *
	 * @return string
	 */
	protected function get_root_dir() : string {
		return dirname( $this->getComposer()->getConfig()->getConfigSource()->getName() );
	}

	/**
	 * Get a module config from composer.json.
	 *
	 * @param string $module The module to get the config for.
	 * @return array
	 */
	protected function get_config( $module = 'dev-tools' ) : array {
		// @codingStandardsIgnoreLine
		$json = file_get_contents( $this->get_root_dir() . DIRECTORY_SEPARATOR . 'composer.json' );
		$composer_json = json_decode( $json, true );

		return (array) $composer_json['extra']['altis']['modules'][ $module ] ?? [];
	}

}
