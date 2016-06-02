<?php

$wp_tests_dir = getenv('WP_TESTS_DIR');
require_once $wp_tests_dir . '/includes/functions.php';

function _manually_load_environment() {
	$plugins_to_active = array("WP-DS-NPR-API/ds-npr-api.php");
	update_option('active_plugins', $plugins_to_active);
}
tests_add_filter('muplugins_loaded', '_manually_load_environment');

require $wp_tests_dir . '/includes/bootstrap.php';
