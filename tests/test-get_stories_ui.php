<?php

class Test_GetStoriesUi extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->post_id = $this->factory->post->create();
	}

	function test_nprstory_add_new_story_columns() {
		$ret = nprstory_add_new_story_columns( array() );
		$this->assertTrue( isset( $ret['update_story'] ) );
	}

	function test_nprstory_update_column_content() {
		$ret = nprstory_update_column_content( 'update_story', $this->post_id );
		$this->assertTrue( is_null( $ret ) );

		update_post_meta( $this->post_id, NPR_RETRIEVED_STORY_META_KEY, true );
		nprstory_update_column_content( 'update_story', $this->post_id );
		$this->expectOutputRegex('/<a href\=".*">.*/');
	}

	function test_nprstory_bulk_action_update_dropdown() {
		# global $post_type must be set
		global $post_type;
		$post_type = 'post';
		# The ds_npr_api_push_url option must be set
		update_option( 'ds_npr_api_push_url', 'test' );
		$this->expectOutputRegex('/<script type\="text\/javascript">.*/');
		nprstory_bulk_action_update_dropdown();
	}

	function test_nprstory_bulk_action_update_action() {
		# TODO: figure out what this function is supposed to do
		# so we can determine how it should be tested
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_nprstory_get_stories() {
		# TODO: figure out what this function is supposed to do
		# so we can determine how it should be tested
		# at the minimum we should be expecting a div.wrap
		# if there is not an API key set, the output changes
		# If there is not an API pull url set, the output changes
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

}
