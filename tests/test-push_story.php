<?php

class Test_PushStory extends WP_UnitTestCase {

	function test_npr_push() {
		# TODO: figure out how to verify a post has been
		# pushed to the API in order to test npr_push
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_npr_delete() {
		# TODO: figure out how to verify a post has been
		# pushed to the API so we can attempt deleting with npr_delete
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_push_add_field_mapping_page() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_push_mapping_callback() {
		# TODO: do we need ds_npr_get_post_meta_keys if it doesn't actually do anything?
		$ret = ds_npr_api_push_mapping_callback();
		$this->assertTrue( is_null( $ret ) );
	}

	function test_ds_npr_push_meta_keys() {
		# Should be empty since our test database contains nothing
		$meta_keys = ds_npr_push_meta_keys();
		$this->assertTrue( empty( $meta_keys ) );
	}

	function test_ds_npr_get_post_meta_keys() {
		$meta_keys = ds_npr_get_post_meta_keys();
		$this->assertTrue( empty( $meta_keys ) );
	}

	function test_ds_npr_push_settings_init() {
		# TODO: not sure how to test this
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_push_settings_callback() {
		# TODO: do we need ds_npr_api_push_settings_callback if it doesn't actually do anything?
		$ret = ds_npr_api_push_settings_callback();
		$this->assertTrue( is_null( $ret ) );
	}

	function test_ds_npr_api_use_custom_mapping_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_title_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_body_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_byline_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_credit_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_media_agency_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_distribute_media_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_api_mapping_distribute_media_polarity_callback() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_show_keys_select() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_get_push_post_type() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_get_permission_groups() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_bulk_action_push_dropdown() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_bulk_action_push_action() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_send_to_nprone() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_save_send_to_npr_one() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

}
