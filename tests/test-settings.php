<?php

class Test_Settings extends WP_UnitTestCase {

	function test_nprstory_add_options_page() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_add_query_page() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_settings_init() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_api_settings_callback() {
		# TODO: do we need nprstory_api_settings_callback if it does nothing?
		$ret = nprstory_api_settings_callback();
		$this->assertTrue( is_null( $ret ) );
	}

	function test_nprstory_add_cron_interval() {
		$test_schedules = array();
		$ret = nprstory_add_cron_interval( $test_schedules );
		$this->assertTrue( isset( $ret['ds_interval'] ) );
	}

	function test_nprstory_api_get_multi_settings_callback() {
		nprstory_api_get_multi_settings_callback();
		$schedules = wp_get_schedule( 'npr_ds_hourly_cron' );
		$this->assertTrue( ! empty( $schedules ) );
	}

	function test_nprstory_query_run_multi_callback() {
		# Simple test of output -- expect an input with id dp_npr_query_run_multi
		$this->expectOutputRegex('/<input id\=\'dp_npr_query_run_multi\'.*/');
		nprstory_query_run_multi_callback();
	}

	function test_nprstory_query_multi_cron_interval_callback() {
		# Simple test of outut -- should output an input element with a label matching below
		$this->expectOutputRegex('/<input.*How often, in minutes, should the Get Multi function run\?  \(default \= 60\)/');
		nprstory_query_multi_cron_interval_callback();
	}

	function test_nprstory_api_query_publish_callback() {
		# Output test -- expect div element plus text shown below, value of
		# 'ds_npr_api_query_publish' . $i option should be included in the output.
		$i = 'test';
		update_option( 'ds_npr_query_publish_' . $i, 'test' );
		$this->expectOutputRegex('/<div>Publish or Draft the returns from Query.*test.*/');
		nprstory_api_query_publish_callback($i);
	}

	function test_nprstory_api_query_callback() {
		# Output test -- make sure passed parameter is used in output of input element
		$i = 'test';
		update_option( 'ds_npr_query_' . $i, 'test' );
		$this->expectOutputRegex(
			'/<input type\=\'text\' value\=\'test\' name\=\'ds_npr_query_test\' style\=\'width: 300px\;\' \/>/');
		nprstory_api_query_callback($i);
	}

	function test_nprstory_api_num_multi_callback() {
		update_option( 'ds_npr_num', 'test' );
		// this should really be a number
		$this->expectOutputRegex('/<input type\=\'number\' value=\'test\'.*/');
		nprstory_api_num_multi_callback();
	}

	function test_nprstory_api_key_callback() {
		update_option( 'ds_npr_api_key', 'test' );
		$this->expectOutputRegex('/<input type\=\'text\' value=\'test\'.*/');
		nprstory_api_key_callback();
	}

	function test_nprstory_api_pull_url_callback() {
		update_option( 'ds_npr_api_pull_url', 'test' );
		$this->expectOutputRegex('/<input type\=\'text\' value=\'test\'.*/');
		nprstory_api_pull_url_callback();
	}

	function test_nprstory_api_push_url_callback() {
		update_option( 'ds_npr_api_push_url', 'test' );
		$this->expectOutputRegex('/<input type\=\'text\' value=\'test\'.*/');
		nprstory_api_push_url_callback();
	}

	function test_nprstory_api_org_id_callback() {
		update_option( 'ds_npr_api_org_id', 'test' );
		$this->expectOutputRegex('/<input type\=\'text\' value=\'test\'.*/');
		nprstory_api_org_id_callback();
	}

	function test_nprstory_pull_post_type_callback() {
		$field_name = 'ds_npr_pull_post_type';
		$this->expectOutputRegex('/<div><select id\=' . $field_name . '.*/');
		nprstory_pull_post_type_callback();
	}

	function test_nprstory_push_post_type_callback() {
		$field_name = 'ds_npr_push_post_type';
		$this->expectOutputRegex('/<div><select id\=' . $field_name . '.*/');
		nprstory_push_post_type_callback();
	}

	function test_nprstory_push_story_permissions_callback() {
		# Expect an error message since we are not actually contacting the API for permissions groups
		$this->expectOutputRegex('/You have no Permission Groups defined with the NPR API/');
		nprstory_push_story_permissions_callback();
	}

	function test_nprstory_show_post_types_select() {
		$field_name = 'test_field';
		update_option( $field_name, 'test_value' );
		$this->expectOutputRegex('/<div><select id\=' . $field_name . '.*/');
		nprstory_show_post_types_select( $field_name, array() );
	}

	function test_nprstory_show_perms_select() {
		$field_name = 'test_field';
		$this->expectOutputRegex('/<div><select id\=' . $field_name . '.*/');
		nprstory_show_perms_select( $field_name, array() );
	}

}
