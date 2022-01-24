<?php
/**
 * NPR API Settings Page and related control methods
 *
 * Also includes the cron jobs.
 */

require_once( NPRSTORY_PLUGIN_DIR . 'settings_ui.php' );

/**
 * add the options page
 *
 * @see nprstory_publish_meta_box_prompt
 */
function nprstory_add_options_page() {
    add_options_page( 'NPR API', 'NPR API', 'manage_options', 'ds_npr_api', 'nprstory_api_options_page' );
}
add_action( 'admin_menu', 'nprstory_add_options_page' );

function nprstory_add_query_page() {
    $num = get_option( 'ds_npr_num' );
    if ( empty($num) ) {
        $num = 1;
	}
	$k = $num;
    $opt = get_option( 'ds_npr_query_' . $k );
    while ($k < NPR_MAX_QUERIES) {
        delete_option( 'ds_npr_query_' . $k );
        $k++;
        $opt = get_option( 'ds_npr_query_' . $k );
    }

    //make sure we remove any queries we didn't want to use
    if ( ! empty($num) && $num < NPR_MAX_QUERIES ) {
        $k = $num;
        $opt = get_option( 'ds_npr_query_' . $k );
        while ( $k < NPR_MAX_QUERIES ) {
            delete_option( 'ds_npr_query_' . $k );
            $k++;
        }
    }
    add_options_page( 'Auto Fetch from the NPR API settings',
        'NPR API Get Multi', 'manage_options',
        'ds_npr_api_get_multi_settings',
        'nprstory_api_get_multi_options_page' );
}
add_action( 'admin_menu', 'nprstory_add_query_page' );

function nprstory_settings_init() {
    add_settings_section( 'ds_npr_api_settings', 'NPR API settings', 'nprstory_api_settings_callback', 'ds_npr_api' );

    add_settings_field( 'ds_npr_api_key', 'API KEY', 'nprstory_api_key_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_key', 'nprstory_validation_callback_api_key' );

    add_settings_field( 'ds_npr_api_pull_url', 'Pull URL', 'nprstory_api_pull_url_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_pull_url', 'nprstory_validation_callback_pull_url' );

    add_settings_field( 'ds_npr_api_push_url', 'Push URL', 'nprstory_api_push_url_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_push_url', 'nprstory_validation_callback_push_url' );

    add_settings_field( 'ds_npr_api_org_id', 'Org ID', 'nprstory_api_org_id_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_org_id', 'nprstory_validation_callback_api_key' );

    add_settings_section( 'ds_npr_api_get_multi_settings', 'NPR API multiple get settings', 'nprstory_api_get_multi_settings_callback', 'ds_npr_api_get_multi_settings' );

    add_settings_field( 'ds_npr_num', 'Number of things to get', 'nprstory_api_num_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );

    add_settings_field( 'ds_npr_num', 'Number of things to get', 'nprstory_api_num_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_num', 'intval' );

	/**
	 * Create a number of forms based off of the number of queries that are registered
	 */
    $num =  get_option( 'ds_npr_num' );
    if ( empty($num) ) {
        $num = 5;
    }
    for( $i = 0; $i < $num; $i++ ) {
        add_settings_field( 'ds_npr_query_' . $i, 'Query String ' . $i, 'nprstory_api_query_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_' . $i , 'nprstory_validation_callback_url');

    	//ds_npr_query_publish_
        add_settings_field( 'ds_npr_query_publish_' . $i, 'Publish Stories ' . $i, 'nprstory_api_query_publish_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_publish_' . $i , 'nprstory_validation_callback_select');
        
        // Add tags
        add_settings_field( 'ds_npr_query_tags_' . $i, 'Add Tags ' . $i, 'ds_npr_api_query_tags_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_tags_' . $i );
    }

    add_settings_field( 'dp_npr_query_run_multi', 'Run the queries on saving changes', 'nprstory_query_run_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'dp_npr_query_run_multi' , 'nprstory_validation_callback_checkbox');


    add_settings_field( 'dp_npr_query_multi_cron_interval', 'Interval to run Get Multi cron', 'nprstory_query_multi_cron_interval_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'dp_npr_query_multi_cron_interval', 'intval' );


    add_settings_field( 'dp_npr_query_use_layout', 'Use rich layout on pulled posts if available', 'nprstory_query_use_layout_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'dp_npr_query_use_layout' , 'nprstory_validation_callback_checkbox');


    add_settings_field( 'ds_npr_pull_post_type', 'NPR Pull Post Type', 'nprstory_pull_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_pull_post_type', 'nprstory_validation_callback_select' );

    add_settings_field( 'ds_npr_push_post_type', 'NPR Push Post Type', 'nprstory_push_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_push_post_type' , 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_story_default_permission', 'NPR Permissions', 'nprstory_push_story_permissions_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_story_default_permission' );

}
add_action( 'admin_init', 'nprstory_settings_init' );

function nprstory_api_settings_callback() { }

function nprstory_add_cron_interval( $schedules ) {
    $ds_interval = get_option( 'dp_npr_query_multi_cron_interval' );
	//if for some reason we don't get a number in the option, use 60 minutes as the default.
	if ( ! is_numeric($ds_interval) || $ds_interval < 1 ) {
        $ds_interval = 60;
		update_option( 'dp_npr_query_multi_cron_interval', 60 );
	}
	$new_interval = $ds_interval * 60;
    $schedules['ds_interval'] = array (
      'interval' => $new_interval,
      'display' => __( 'DS Cron, run Once every ' . $ds_interval . ' minutes' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'nprstory_add_cron_interval' );

function nprstory_api_get_multi_settings_callback() {
	$run_multi = get_option( 'dp_npr_query_run_multi' );
	if ( $run_multi ) {
		DS_NPR_API::nprstory_cron_pull();
	}

	//change the cron timer
	if ( wp_next_scheduled( 'npr_ds_hourly_cron' ) ) {
		wp_clear_scheduled_hook( 'npr_ds_hourly_cron' );
	}
	nprstory_error_log( 'NPR Story API plugin: updating the npr_ds_hourly_cron event timer' );
	wp_schedule_event( time(), 'ds_interval', 'npr_ds_hourly_cron');
}

function nprstory_query_run_multi_callback() {
	$run_multi = get_option('dp_npr_query_run_multi');
	$check_box_string = "<input id='dp_npr_query_run_multi' name='dp_npr_query_run_multi' type='checkbox' value='true'";

	if ( $run_multi ) {
		$check_box_string .= ' checked="checked" ';
	}
	$check_box_string .= "/>";

	echo $check_box_string;
	wp_nonce_field( 'nprstory_nonce_ds_npr_query_run_multi', 'nprstory_nonce_ds_npr_query_run_multi_name', true, true );
}

function nprstory_query_use_layout_callback() {
  $use_layout = get_option('dp_npr_query_use_layout');
  $check_box_string = "<input id='dp_npr_query_use_layout' name='dp_npr_query_use_layout' type='checkbox' value='true'";

  if ( $use_layout ) {
    $check_box_string .= ' checked="checked" ';
  }
  $check_box_string .= "/>";

  echo $check_box_string . "<p>If 'layout' is available in the NPR Story API output for your key, checking this box will import posts with more complex HTML to render any images, YouTube videos, Tweets, iframes, or JavaScript-based widgets within the post in the order they appeared on the NPR website. Only the 'primary' image for the story will be sideloaded into the Media Library, all other images will be linked from NPR.  <br/>If 'layout' is not available in your API results and you want to use this feature, open a ticket with NPR requesting 'layout permissions' for your Story API Key, and include the key in the ticket request.  <br/>CAUTION: Checking this box will disables the normal 'wp_kses' filtering for imported posts that prevents any JavaScript from being included in the post.  We assume that NPR Story API posts will not have malicious scripts. </p>";
  wp_nonce_field( 'nprstory_nonce_ds_npr_query_use_layout', 'nprstory_nonce_ds_npr_query_use_layout_name', true, true );
}

function nprstory_query_multi_cron_interval_callback() {
	$option = get_option( 'dp_npr_query_multi_cron_interval' );
	echo "<input type='number' value='$option' name='dp_npr_query_multi_cron_interval' id='dp_npr_query_multi_cron_interval' /> <p> How often, in minutes, should the Get Multi function run?  (default = 60)";
	wp_nonce_field( 'nprstory_nonce_ds_npr_query_multi_cron_interval', 'nprstory_nonce_ds_npr_query_multi_cron_interval_name', true, true );
}

function nprstory_api_query_publish_callback($i) {
	$selected = get_option( 'ds_npr_query_publish_' . $i );

	echo "<div>Publish or Draft the returns from Query " . $i . "? <select id=" . 'ds_npr_query_publish_' . $i . " name=" . 'ds_npr_query_publish_' . $i . ">";

	//echo '<option value=""> &mdash; Select &mdash; </option>';
	$keys = array( "Publish", "Draft" );
	foreach ( $keys as $key ) {
		$option_string = "\n<option  ";
		if ($key == $selected) {
            $option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr($key) . "'>" . esc_html($key) . " </option>";
		echo $option_string;
	}
	$option_string .= wp_nonce_field( 'nprstory_nonce_ds_npr_query_publish_' . $i, 'nprstory_nonce_ds_npr_query_publish_' . $i . '_name', true, false );
    echo "</select> </div>";
}

function nprstory_api_query_callback( $i ) {
	$option = get_option( 'ds_npr_query_' . $i );
	$name = 'ds_npr_query_' . $i;
	echo "<input type='text' value='$option' name='$name' style='width: 300px;' />";
	wp_nonce_field( 'nprstory_nonce_ds_npr_query_' . $i, 'nprstory_nonce_ds_npr_query_' . $i . '_name', true, true );

}

function ds_npr_api_query_tags_callback( $i ) {
	$name = 'ds_npr_query_tags_' . $i;
	$option = get_option( $name );
	
	echo "<input type='text' value='$option' name='$name' style='width: 300px;' /> <p> Add tag(s) to each story pulled from NPR (comma separated).</p>";
  wp_nonce_field( 'nprstory_nonce_ds_npr_tags_' . $i, 'nprstory_nonce_ds_npr_tags_' . $i . '_name', true, true );
	echo "<p><hr></p>";
}

function nprstory_api_num_multi_callback() {
	$option = get_option('ds_npr_num');
	echo "<input type='number' value='$option' name='ds_npr_num' /> <p> Increase the number of queries by changing the number in the field above.";
	wp_nonce_field( 'nprstory_nonce_ds_npr_num', 'nprstory_nonce_ds_npr_num_name', true, true );
}

function nprstory_api_key_callback() {
    $option = get_option( 'ds_npr_api_key' );
    echo "<input type='text' value='$option' name='ds_npr_api_key' style='width: 300px;' />";
	wp_nonce_field( 'nprstory_nonce_ds_npr_api_key', 'nprstory_nonce_ds_npr_api_key_name', true, true );
}

function nprstory_api_pull_url_callback() {
    $option = get_option( 'ds_npr_api_pull_url' );
    echo "<input type='text' value='$option' name='ds_npr_api_pull_url' style='width: 300px;' />";
	wp_nonce_field( 'nprstory_nonce_ds_npr_api_pull_url', 'nprstory_nonce_ds_npr_api_pull_url_name', true, true );
}

function nprstory_api_push_url_callback() {
    $option = get_option( 'ds_npr_api_push_url' );
    echo "<input type='text' value='$option' name='ds_npr_api_push_url' style='width: 300px;' />";
	wp_nonce_field( 'nprstory_nonce_ds_npr_api_push_url', 'nprstory_nonce_ds_npr_api_push_url_name', true, true );
}

function nprstory_api_org_id_callback() {
    $option = get_option( 'ds_npr_api_org_id' );
    echo "<input type='text' value='$option' name='ds_npr_api_org_id' style='width: 300px;' />";
	wp_nonce_field( 'nprstory_nonce_ds_npr_api_org_id', 'nprstory_nonce_ds_npr_api_org_id_name', true, true );
}

function nprstory_pull_post_type_callback() {
	$post_types = get_post_types();
	nprstory_show_post_types_select( 'ds_npr_pull_post_type', $post_types );
}

function nprstory_push_post_type_callback() {
	$post_types = get_post_types();
	nprstory_show_post_types_select( 'ds_npr_push_post_type', $post_types );
    echo ('<div> If you change the Push Post Type setting remember to update the mappings for API Fields at <a href="' . admin_url('options-general.php?page=ds_npr_api_push_mapping') . '">NPR API Field Mapping </a> page.</div>');
}

function nprstory_push_story_permissions_callback() {
    $permissions_groups = nprstory_get_permission_groups();

	if (!empty($permissions_groups)){
		nprstory_show_perms_select( 'ds_npr_story_default_permission', $permissions_groups );
		echo ('<div> This is where you select the default permissions group to use when pushing stories to the NPR API.</div>');
	} else {
		echo ('<div> You have no Permission Groups defined with the NPR API. </div>');
	}
}

/**
* create the select widget where the Id is the value in the array
* @param  $field_name
* @param  $keys - an array like (1=>'Value1', 2=>'Value2', 3=>'Value3');
*/
function nprstory_show_post_types_select( $field_name, $keys ){
	$selected = get_option( $field_name );

	echo "<div><select id=" . $field_name . " name=" . $field_name . ">";

	echo '<option value=""> &mdash; Select &mdash; </option>';
	foreach ( $keys as $key ) {
        $option_string = "\n<option  ";
		if ($key == $selected) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . " </option>";
		echo $option_string;
	}
	echo "</select> </div>";
	wp_nonce_field( 'nprstory_nonce_' . $field_name, 'nprstory_nonce_' . $field_name . '_name', true, true );
}

/**
 * create the select widget where the ID for an element is the index to the array
 * @param  $field_name
 * @param  $keys an array like (id1=>'Value1', id2=>'Value2', id3=>'Value3');
 */
function nprstory_show_perms_select( $field_name, $keys ){
	$selected = get_option( $field_name );
	echo "<div><select id=" . $field_name . " name=" . $field_name . ">";

	echo '<option value=""> &mdash; Select &mdash; </option>';
	foreach ( $keys as $id => $key ) {
		$option_string = "\n<option  ";
		if ($id == $selected) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr($id) . "'>" . esc_html($key['name']) . " </option>";
		echo $option_string;
	}
	echo "</select> </div>";
	wp_nonce_field( 'nprstory_nonce_' . $field_name, 'nprstory_nonce_' . $field_name . '_name', true, true );
}
