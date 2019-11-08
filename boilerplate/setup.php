<?php
/**
 * Tests setup script.
 *
 * Runs during the bootstrap process after test suite functions have loaded.
 */

// Example of adding hooks early to setup your tests.
tests_add_filter( 'muplugins_loaded', function () {
	// Add actions and set up steps that require WordPress to be fully loaded here.
} );
