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
			new InputArgument( 'subcommand', InputArgument::REQUIRED, 'phpunit, scaffold' ),
			new InputOption( 'chassis', null, null, 'Run commands in the Local Chassis environment' ),
			new InputArgument( 'options', InputArgument::IS_ARRAY ),
		] );
		$this->setHelp(
			<<<EOT
Run a dev tools feature.

To run PHPUnit integration tests:
    phpunit [--] [options]      use `--` to separate arguments you want to
                                pass to phpunit
Create scaffolding for various features:
    scaffold <type>             possible values are 'phpunit', defaults to
                                'phpunit'
Global options:
    [--chassis]   Passing this instructs dev tools to run the command on your
                  local chassis instance. By default this command looks for
                  local server.
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
				return $this->run_command( $input, $output, 'vendor/bin/phpunit' );

			case 'scaffold':
				return $this->scaffold( $input, $output );

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $subcommand ) );
		}
	}

	/**
	 * Create base test runner files.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function scaffold( InputInterface $input, OutputInterface $output ) {
		// Scaffold PHPUnit by default.
		$target = $input->getArgument( 'options' )[0] ?? 'phpunit';

		switch ( $target ) {
			case 'phpunit':
				return $this->scaffold_phpunit( $output );

			default:
				throw new CommandNotFoundException( sprintf( '"%s" is not a recognised scaffold value.', $target ) );
		}
	}

	protected function scaffold_phpunit( OutputInterface $output ) {
		$package_root = dirname( __DIR__, 2 );

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
			$output->writeln( '<info>Copying phpunit.xml.dist</>' );
			copy( $package_root . '/boilerplate/phpunit.xml.dist', $this->get_root_dir() . '/phpunit.xml.dist' );
		}

		// Copy files over.
		$output->writeln( '<info>Creating tests directory...</>' );
		mkdir( $this->get_root_dir() . '/tests', 0755, true );
		$output->writeln( '<info>Copying bootstrap.php</>' );
		copy( $package_root . '/boilerplate/bootstrap.php', $this->get_root_dir() . '/tests/bootstrap.php' );
		$output->writeln( '<info>Copying config.php</>' );
		copy( $package_root . '/boilerplate/config.php', $this->get_root_dir() . '/tests/config.php' );
		$output->writeln( '<info>Copying test-sample.php</>' );
		copy( $package_root . '/boilerplate/test-sample.php', $this->get_root_dir() . '/tests/test-sample.php' );

		$output->writeln( '<info>Success!</>' );
		return 0;
	}

	protected function run_command( InputInterface $input, OutputInterface $output, $command ) {
		$use_chassis = $input->getOption( 'chassis' );
		$cli = $this->getApplication()->find( $use_chassis ? 'chassis' : 'local-server' );
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

}
