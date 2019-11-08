<?php
/**
 * Tests Config file.
 *
 * Add any custom configuration you require for your test environment here.
 *
 * @phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols
 */

defined( 'WP_TESTS_DOMAIN' ) or define( 'WP_TESTS_DOMAIN', 'example.org' );
defined( 'WP_TESTS_EMAIL' ) or define( 'WP_TESTS_EMAIL', 'admin@example.org' );
defined( 'WP_TESTS_TITLE' ) or define( 'WP_TESTS_TITLE', 'Test Blog' );

//===============================//
// DO NOT EDIT BELOW THIS LINE!  //
//===============================//

// Set tests table prefix.
$table_prefix = 'wptests_';

//.Load Altis.
require_once dirname( __DIR__ ) . '/wp-config.php';
