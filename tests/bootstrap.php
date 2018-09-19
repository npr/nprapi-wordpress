<?php
/**
 * PHPUnit bootstrap file
 */

$wp_tests_dir = getenv('WP_TESTS_DIR');
if ( ! $wp_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $wp_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Make sure WordPress knows the plugin should be active
 */
function _manually_load_environment() {
	$plugins_to_active = array("WP-DS-NPR-API/ds-npr-api.php");
	update_option( 'active_plugins', $plugins_to_active );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/ds-npr-api.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $wp_tests_dir . '/includes/bootstrap.php';
