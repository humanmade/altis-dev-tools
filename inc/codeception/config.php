<?php
/**
 * Altis Tests Config file.
 *
 * phpcs:disable PSR1.Files.SideEffects
 *
 * @package altis/dev-tools
 */

// Command is always executed from the project root on local servers.
if ( ! defined( 'Altis\\PHPUNIT_PROJECT_ROOT' ) ) {
	define( 'Altis\\PHPUNIT_PROJECT_ROOT', getcwd() );
}

defined( 'WP_TESTS_DOMAIN' ) or define( 'WP_TESTS_DOMAIN', 'example.org' );
defined( 'WP_TESTS_EMAIL' ) or define( 'WP_TESTS_EMAIL', 'admin@example.org' );
defined( 'WP_TESTS_TITLE' ) or define( 'WP_TESTS_TITLE', 'Test Blog' );

// Ensure cache key salt is different for test runs.
defined( 'WP_CACHE_KEY_SALT' ) or define( 'WP_CACHE_KEY_SALT', 'codeception_ssas' );

// Ensure tests use their own ElasticPress indexes.
defined( 'EP_INDEX_PREFIX' ) or define( 'EP_INDEX_PREFIX', 'tests_' );

$table_prefix = getenv( 'TEST_TABLE_PREFIX' );

// Load Altis.
require_once Altis\PHPUNIT_PROJECT_ROOT . '/wp-config.php';
