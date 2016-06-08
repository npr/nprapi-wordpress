<?php

class Test_SettingsUi extends WP_UnitTestCase {

	function test_ds_npr_api_options_age() {
		# Basic test of output -- expect to see the title of the page in the output
		$this->expectOutputRegex('/NPR API settings/');
		ds_npr_api_options_page();
	}

	function test_ds_npr_api_get_multi_options_page() {
		# Basic test of output -- expect to see the title of the page in the output
		$this->expectOutputRegex('/Create an NPR API query/');
		ds_npr_api_get_multi_options_page();
	}

	function test_ds_npr_add_field_mapping_page() {
		# Basic test of output -- expect to see a form printed in the output
		$this->expectOutputRegex('/<form action\="options\.php" method\="post">/');
		ds_npr_add_field_mapping_page();
	}

}
