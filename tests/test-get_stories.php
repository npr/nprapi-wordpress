<?php

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return WP_ADMIN;
	}
}

class FakeAdmin {
	function in_admin() { return true; }
}

class Test_DS_NPR_API_Class extends WP_UnitTestCase {

	function test_nprstory_get_pull_post_type() {
		$post_type = DS_NPR_API::nprstory_get_pull_post_type();
		$this->assertEquals( 'post', $post_type );

		update_option( 'ds_npr_pull_post_type', 'test_post_type' );
		$post_type = DS_NPR_API::nprstory_get_pull_post_type();
		$this->assertEquals( 'test_post_type', $post_type );
	}

	function test_nprstory_cron_pull() {
		# TODO: we'll need to set up some test queries for this test.
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_load_page_hook() {
		# TODO: not sure what this function does and thus not sure how it should be tested.
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_DS_NPR_API() {
		$test_obj = new DS_NPR_API;

		# Should be false when not in admin context
		$this->assertFalse( (bool) has_action( 'load-posts_page_get-npr-stories', array( 'DS_NPR_API', 'load_page_hook' ) ) );
		$this->assertFalse( (bool) has_action( 'admin_menu', array( &$test_obj, 'admin_menu' ) ) );

		# Should be true when in admin context
		if ( isset( $GLOBALS['current_screen'] ) ) {
			$tmp = $GLOBALS['current_screen'];
		}
		$GLOBALS['current_screen'] = new FakeAdmin();

		# Re-create the $test_obj after setting admin context
		$test_obj = new DS_NPR_API;

		$this->assertTrue( (bool) has_action( 'load-posts_page_get-npr-stories', array( $test_obj, 'load_page_hook' ) ) );
		$this->assertTrue( (bool) has_action( 'admin_menu', array( &$test_obj, 'admin_menu' ) ) );

		# Restore globals
		unset( $GLOBALS['current_screen'] );
		if ( isset ( $tmp ) ) {
			$GLOBALS['current_screen'] = $tmp;
		}
	}

	function test_admin_menu() {
		$this->markTestSkipped('Functional test performed by Test_DS_NPR_API_Class::test_DS_NPR_API');
	}

}
