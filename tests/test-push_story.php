<?php

class Test_PushStory extends WP_UnitTestCase {

	function test_nprstory_api_push() {
		# TODO: figure out how to verify a post has been
		# pushed to the API in order to test nprstory_api_push.
		# NOTE: This requires API credentials and API access
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_api_delete() {
		# TODO: figure out how to verify a post has been
		# pushed to the API so we can attempt deleting with nprstory_api_delete
		# NOTE: This requires API credentials and API access
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_push_add_field_mapping_page() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_api_push_mapping_callback() {
		# TODO: do we need nprstory_get_post_meta_keys if it doesn't actually do anything?
		$ret = nprstory_api_push_mapping_callback();
		$this->assertTrue( is_null( $ret ) );
	}

	function test_nprstory_push_meta_keys() {
		# Should be empty since our test database contains nothing
		$meta_keys = nprstory_push_meta_keys();
		$this->assertTrue( empty( $meta_keys ) );
	}

	function test_nprstory_get_post_meta_keys() {
		$meta_keys = nprstory_get_post_meta_keys();
		$this->assertTrue( empty( $meta_keys ) );
	}

	function test_nprstory_push_settings_init() {
		# TODO: not sure how to test this
		# perhaps checking the globals used by add_settings_field and register_setting and add_settings_section
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_api_push_settings_callback() {
		# TODO: do we need nprstory_api_push_settings_callback if it doesn't actually do anything?
		$ret = nprstory_api_push_settings_callback();
		$this->assertTrue( is_null( $ret ) );
	}

	function test_nprstory_api_use_custom_mapping_callback() {
		# Simple test of output -- should include an input with id dp_npr_push_use_custom_map
		$this->expectOutputRegex('/<input id\=\'dp_npr_push_use_custom_map\'.*/');
		nprstory_api_use_custom_mapping_callback();
	}

	function test_nprstory_api_mapping_title_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_api_mapping_body_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_api_mapping_byline_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_ds_npr_api_mapping_credit_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_api_mapping_media_agency_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_api_mapping_distribute_media_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_api_mapping_distribute_media_polarity_callback() {
		$this->markTestSkipped('Relies on nprstory_get_post_meta_keys which returns an empty array during tests');
	}

	function test_nprstory_show_keys_select() {
		$this->expectOutputRegex('/test_field_name/');
		$this->expectOutputRegex('/test_key/');
		nprstory_show_keys_select( 'test_field_name', array( 'test_key' ) );
	}

	function test_nprstory_get_push_post_type() {
		$ret = nprstory_get_push_post_type();
		$this->assertEquals( 'post', $ret );

		# Should return the value of ds_npr_push_post_type option if it is set
		update_option( 'ds_npr_push_post_type', 'test_post' );
		$ret = nprstory_get_push_post_type();
		$this->assertEquals( $ret, 'test_post' );
	}

	function test_nprstory_get_permission_groups() {
		# TODO: this requires API credentials and API access
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_bulk_action_push_dropdown() {
		global $post_type;
		$post_type = 'post';
		update_option( 'ds_npr_api_push_url', 'test' );
		$this->expectOutputRegex('/<script type\="text\/javascript">.*/');
		nprstory_bulk_action_push_dropdown();
	}

	function test_ds_npr_bulk_action_push_action() {
		# TODO: another one that requires API credentials and API access
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_send_to_nprone() {
		$post_id = $this->factory->post->create();
		global $post;
		$tmp = $post;
		$post = get_post( $post_id );
		setup_postdata( $post );
		update_option( 'ds_npr_push_post_type', 'post' );
		# Simple test of output to verify some part of the expected markup is present
		$this->expectOutputRegex('/<div class\="misc-pub-section misc-pub-section-last"/');
		send_to_nprone();
		$post = $tmp;
		wp_reset_postdata();
	}

	function test_save_send_to_npr_one() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

}
