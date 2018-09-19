<?php

class Test_MetaBoxes extends WP_UnitTestCase {
	/**
	 * Test the meta box
	 */
	function test_nprstory_publish_meta_box() {
		$post_id = $this->factory->post->create();
		global $post;
		$tmp = $post;
		$post = get_post( $post_id );
		setup_postdata( $post );
		update_option( 'ds_npr_push_post_type', 'post' );

		# Simple test of output to verify some part of the expected markup is present
		$this->expectOutputRegex('/<div id\="ds-npr-publish-actions"/');
		nprstory_publish_meta_box( $post );

		/*
		 * @todo:
		 * assert that jquery-ui is enqueued
		 * assert that nprstory_publish_meta_box_stylesheet is enqueued
		 * assert that nprstory_publish_meta_box_script is enqueued
		 *
		 * assert that the checked values of the check boxes match the post's meta values
		 * assert that the expiry date matches the post's meta values
		 */

		// reset
		$post = $tmp;
		wp_reset_postdata();
	}

	/**
	 * Test that the assets for the meta box are registered
	 */
	function test_nprstory_publish_meta_box_assets() {
		// bare minimum test: the function runs
		nprstory_publish_meta_box_assets();
	}
}

