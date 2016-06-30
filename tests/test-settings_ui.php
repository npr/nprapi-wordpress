<?php

class Test_SettingsUi extends WP_UnitTestCase {

	function test_ds_npr_api_options_age() {
		# Basic test of output -- expect to see a form and an input and a submit
		$this->expectOutputRegex('/form/');
		$this->expectOutputRegex('/input/');
		$this->expectOutputRegex('/submit/');
		nprstory_api_options_page();
	}

	function test_nprstory_api_get_multi_options_page() {
		# Basic test of output -- expect to see a form and an input and a submit
		$this->expectOutputRegex('/form/');
		$this->expectOutputRegex('/input/');
		$this->expectOutputRegex('/submit/');
		nprstory_api_get_multi_options_page();
	}

	function test_nprstory_add_field_mapping_page() {
		# Basic test of output -- expect to see a form printed in the output
		$this->expectOutputRegex('/<form action\="options\.php" method\="post">/');
		$this->expectOutputRegex('/input/');
		$this->expectOutputRegex('/submit/');
		nprstory_add_field_mapping_page();
	}

}
