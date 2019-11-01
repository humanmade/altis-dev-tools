<?php

namespace Altis\Dev_Tools\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
		// $this->setDefinition();
		$this->addArgument(
			'subcommand',
			InputArgument::REQUIRED,
			'Subcommand to run'
		);
		$this->addOption( 'chassis', null, null, 'Run commands in the Local Chassis environment' );
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
		$use_chassis = $input->getOption( 'chassis' );

		$command = $use_chassis ? 'run_chassis_command' : 'run_docker_command';

		switch ( $subcommand ) {
			case 'phpunit':
				$this->$command( $input, $output, 'vendor/bin/phpunit' );
				break;

			case 'scaffold':
				$this->scaffold( $input, $output );
				break;

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $subcommand ) );
		}
	}

	/**
	 * Create required test runner files.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function scaffold( InputInterface $input, OutputInterface $output ) {
		// Check for an existing tests directory.
		if ( file_exists( $this->get_root_dir() . '/tests' ) ) {
			$output->writeln( '<error>You already have a "tests" directory present in your project!</>' );
			$output->writeln( '<comment>If you want to compare the recommended Altis testing setup you can temporarily rename your current tests directory and run this command again.</>' );
			return 1;
		}

		if ( file_exists( $this->get_root_dir() . '/phpunit.xml.dist' ) ) {
			$output->writeln( '<error>phpunit.xml.dist file found, skipping</>' );
			$output->writeln( '<comment>You should ensure your version of the file uses the same values shown in this basic outline:</>' );
			$output->write(
				'<phpunit bootstrap="tests/bootstrap.php">' . "\n" .
				"\t" . '<php>' . "\n" .
				"\t\t" . '<env name="WP_PHPUNIT__TESTS_CONFIG" value="tests/config.php" />' . "\n" .
				"\t\t" . '<const name="WP_PHP_BINARY" value="/usr/bin/env php" />' . "\n" .
				"\t" . '</php>' . "\n" .
				'</phpunit>'
			);
		} else {
			copy( __DIR__ . '/boilerplate/phpunit.xml.dist', $this->get_root_dir() . '/phpunit.xml.dist' );
		}

		// Copy files over.
		mkdir( $this->get_root_dir() . '/tests', 0755, true );
		copy( __DIR__ . '/boilerplate/bootstrap.php', $this->get_root_dir() . '/tests/bootstrap.php' );
		copy( __DIR__ . '/boilerplate/config.php', $this->get_root_dir() . '/tests/config.php' );
		copy( __DIR__ . '/boilerplate/test-sample.php', $this->get_root_dir() . '/tests/test-sample.php' );

		return 0;
	}

	/**
	 * Run a command in the Chassis directory.
	 *
	 * @param string $command Command to execute
	 * @return int Status returned from the command
	 */
	protected function run_chassis_command( InputInterface $input, OutputInterface $output, $command ) {
		$cli = $this->getApplication()->find( 'chassis' );
	}

	protected function run_docker_command( InputInterface $input, OutputInterface $output, $command ) {
		$cli = $this->getApplication()->find( 'local-server' );
		$options = $input->getArgument( 'options' );

		// Add the command to the start of the options.
		array_unshift( $options, $command );

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

	protected function get_project_subdomain() {
		$config = $this->get_config( 'local-server' );

		if ( isset( $config['name'] ) ) {
			$project_name = $config['name'];
		} else {
			$project_name = basename( getcwd() );
		}

		return preg_replace( '/[^A-Za-z0-9\-\_]/', '', $project_name );
	}

}
