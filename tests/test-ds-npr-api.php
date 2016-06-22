<?php

class Test_DsNprApi extends WP_UnitTestCase {

	function test_ds_npr_story_activation() {
		ds_npr_story_activation();

		$result = wp_next_scheduled( 'npr_ds_hourly_cron' );
		$this->assertTrue( ! empty( $result ) );

		$option = get_option( 'ds_npr_num' );
		$this->assertEquals( $option, 5 );

		$option = get_option( 'ds_npr_api_pull_url' );
		$this->assertEquals( $option, 'http://api.npr.org' );
	}

	function test_nprstory_activate() {
		$this->markTestSkipped(
			'Functional test of nprstory_activate performed by Test_DsNprApi::test_ds_npr_story_activation');
	}

	function test_ds_npr_story_deactivation() {
		ds_npr_story_deactivation();

		$result = wp_next_scheduled( 'npr_ds_hourly_cron' );
		$this->assertTrue( empty( $result ) );

		$option = get_option( 'ds_npr_num', false );
		$this->assertFalse( $option );

		$option = get_option( 'ds_npr_api_pull_url', false );
		$this->assertFalse( $option );
	}

	function test__ds_npr_deactivate() {
		$this->markTestSkipped(
			'Functional test of __ds_npr_deactivate performed by Test_DsNprApi::test_ds_npr_story_deactivation');
	}

	function test_ds_npr_show_message() {
		$test_message = 'Test message';
		ob_start();
		ds_npr_show_message( $test_message, false );
		$result = ob_get_clean();
		$this->assertTrue( (bool) strstr( $result, $test_message ) );

		ob_start();
		ds_npr_show_message( $test_message, true );
		$result = ob_get_clean();
		$this->assertTrue( (bool) strstr( $result, 'class="error"' ) );
	}

	function test_ds_npr_create_post_type() {
		ds_npr_create_post_type();
		$post_types = get_post_types();
		$this->assertTrue( in_array( NPR_POST_TYPE, $post_types ) );
	}

}
