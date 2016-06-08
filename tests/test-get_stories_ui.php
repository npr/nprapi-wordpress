<?php

class Test_GetStoriesUi extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->post_id = $this->factory->post->create();
	}

	function test_add_new_story_columns() {
		$ret = add_new_story_columns( array() );
		$this->assertTrue( isset( $ret['update_story'] ) );
	}

	function test_ds_npr_update_column_content() {
		$ret = ds_npr_update_column_content( 'update_story', $this->post_id );
		$this->assertTrue( is_null( $ret ) );

		update_post_meta( $this->post_id, NPR_RETRIEVED_STORY_META_KEY, true );
		ds_npr_update_column_content( 'update_story', $this->post_id );
		$this->expectOutputRegex('/<a href\=".*">.*/');
	}

	function test_ds_npr_bulk_action_update_dropdown() {
		global $post_type;
		$post_type = 'post';
		$this->expectOutputRegex('/<script type\="text\/javascript">.*/');
		ds_npr_bulk_action_update_dropdown();
	}

	function test_ds_npr_bulk_action_update_action() {
		# TODO: figure out what this function is supposed to do
		# so we can determine how it should be tested
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_ds_npr_get_stories() {
		# TODO: figure out what this function is supposed to do
		# so we can determine how it should be tested
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

}
