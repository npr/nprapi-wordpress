<?php
require_once( 'settings_ui.php' );
/*
 * NPR API Settings Page and related control methods.
 */
function ds_npr_add_options_page() {
    add_options_page( 'NPR API', 'NPR API', 'manage_options',
                      'ds_npr_api', 'ds_npr_api_options_page' );
    
}
function ds_npr_add_query_page() {
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
        'ds_npr_api_get_multi_options_page' );
}

add_action( 'admin_menu', 'ds_npr_add_options_page' );
add_action( 'admin_menu', 'ds_npr_add_query_page' );

function ds_npr_settings_init() {
    add_settings_section( 'ds_npr_api_settings', 'NPR API settings', 'ds_npr_api_settings_callback', 'ds_npr_api' );

    add_settings_field( 'ds_npr_api_key', 'API KEY', 'ds_npr_api_key_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_key' );

    add_settings_field( 'ds_npr_api_pull_url', 'Pull URL', 'ds_npr_api_pull_url_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_pull_url' );
    
    add_settings_field( 'ds_npr_api_push_url', 'Push URL', 'ds_npr_api_push_url_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_push_url' );
    
    add_settings_field( 'ds_npr_api_org_id', 'Org ID', 'ds_npr_api_org_id_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_api_org_id' );

    add_settings_section( 'ds_npr_api_get_multi_settings', 'NPR API multiple get settings', 'ds_npr_api_get_multi_settings_callback', 'ds_npr_api_get_multi_settings' );

    add_settings_field( 'ds_npr_num', 'Number of queries to run', 'ds_npr_api_num_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_num' );
    $num =  get_option( 'ds_npr_num' );
    if ( empty($num) ) {
        $num = 5;
    }
    for( $i = 0; $i < $num; $i++ ) {
        add_settings_field( 'ds_npr_query_' . $i, 'Query String ' . $i, 'ds_npr_api_query_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_' . $i );
    	
    	//ds_npr_query_publish_
        add_settings_field( 'ds_npr_query_publish_' . $i, 'Publish Stories ' . $i, 'ds_npr_api_query_publish_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_publish_' . $i );

      // add tag(s) to imported stories
        add_settings_field( 'ds_npr_query_tags_' . $i, 'Add Tags ' . $i, 'ds_npr_api_query_tags_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
        register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_tags_' . $i );
    }
    
    add_settings_field( 'dp_npr_query_run_multi', 'Run the queries on saving changes', 'dp_npr_query_run_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'dp_npr_query_run_multi' );
    
    add_settings_field( 'dp_npr_query_multi_cron_interval', 'Interval to run Get Multi cron', 'dp_npr_query_multi_cron_interval_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'dp_npr_query_multi_cron_interval' );
    
    add_settings_field( 'ds_npr_pull_post_type', 'NPR Pull Post Type', 'ds_npr_pull_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_pull_post_type' );
    
    add_settings_field( 'ds_npr_push_post_type', 'NPR Push Post Type', 'ds_npr_push_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_push_post_type' );
    
    add_settings_field( 'ds_npr_story_default_permission', 'NPR Permissions', 'ds_npr_push_story_permissions_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_story_default_permission' );

}

add_action( 'admin_init', 'ds_npr_settings_init' );

function ds_npr_api_settings_callback() { }

add_filter( 'cron_schedules', 'cron_add_ds_interval' );

function cron_add_ds_interval( $schedules ) {
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
 
function ds_npr_api_get_multi_settings_callback() { 
	$run_multi = get_option( 'dp_npr_query_run_multi' );
	if ( $run_multi ) {
	 	DS_NPR_API::ds_npr_story_cron_pull();
	}
	 
	//change the cron timer
	if ( wp_next_scheduled( 'npr_ds_hourly_cron' ) ) {
		wp_clear_scheduled_hook( 'npr_ds_hourly_cron' );
	}
	error_log('updating the cron event');
	wp_schedule_event( time(), 'ds_interval', 'npr_ds_hourly_cron');
	
}

function dp_npr_query_run_multi_callback() {
	$run_multi = get_option('dp_npr_query_run_multi');
	$check_box_string = "<input id='dp_npr_query_run_multi' name='dp_npr_query_run_multi' type='checkbox' value='true'";

	if ( $run_multi ) {
		$check_box_string .= ' checked="checked" ';
	}
	$check_box_string .= "/>";

	echo $check_box_string;
}

function dp_npr_query_multi_cron_interval_callback() {
	$option = get_option( 'dp_npr_query_multi_cron_interval' );
	echo "<input type='text' value='$option' name='dp_npr_query_multi_cron_interval' id='dp_npr_query_multi_cron_interval' style='width: 30px;' /> <p> How often, in minutes, should the Get Multi function run?  (default = 60)";
}

function ds_npr_api_query_publish_callback( $i ){
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
    echo "</select> </div>";
}

function ds_npr_api_query_callback( $i ) {
	$option = get_option( 'ds_npr_query_' . $i );
	$name = 'ds_npr_query_' . $i;
	echo "<input type='text' value='$option' name='$name' style='width: 300px;' />";
}

function ds_npr_api_query_tags_callback( $i ) {
	$name = 'ds_npr_query_tags_' . $i;
	$option = get_option( $name );
	
	echo "<input type='text' value='$option' name='$name' style='width: 300px;' /> <p> Add tag(s) to each story pulled from NPR (comma separated). The NPR tag is added automatically. </p>";
	echo "<p><hr></p>";
}

function ds_npr_api_num_multi_callback() {
	$option = get_option('ds_npr_num');
	echo "<input type='text' value='$option' name='ds_npr_num' style='width: 30px;' /> <p> Increase the number of queries by changing the number in the field above.";
}

function ds_npr_api_key_callback() {
    $option = get_option( 'ds_npr_api_key' );
    echo "<input type='text' value='$option' name='ds_npr_api_key' style='width: 300px;' />"; 
}

function ds_npr_api_pull_url_callback() {
    $option = get_option( 'ds_npr_api_pull_url' );
    echo "<input type='text' value='$option' name='ds_npr_api_pull_url' style='width: 300px;' />"; 
}

function ds_npr_api_push_url_callback() {
    $option = get_option( 'ds_npr_api_push_url' );
    echo "<input type='text' value='$option' name='ds_npr_api_push_url' style='width: 300px;' />"; 
}

function ds_npr_api_org_id_callback() {
    $option = get_option( 'ds_npr_api_org_id' );
    echo "<input type='text' value='$option' name='ds_npr_api_org_id' style='width: 300px;' />"; 
}

function ds_npr_pull_post_type_callback() {
	$post_types = get_post_types();
	ds_npr_show_post_types_select( 'ds_npr_pull_post_type', $post_types );
}

function ds_npr_push_post_type_callback() {
	$post_types = get_post_types();
	ds_npr_show_post_types_select( 'ds_npr_push_post_type', $post_types );
    echo ('<div> If you change the Push Post Type setting remember to update the mappings for API Fields at <a href="' . admin_url('options-general.php?page=ds_npr_api_push_mapping') . '">NPR API Field Mapping </a> page.</div>');
	
}

function ds_npr_push_story_permissions_callback() {
    $permissions_groups = ds_npr_get_permission_groups();
	
	if (!empty($permissions_groups)){
		ds_npr_show_perms_select( 'ds_npr_story_default_permission', $permissions_groups );
		echo ('<div> This is where you select the default permissions group to use when pushing stories to the NPR API.</div>');
	} else {
		echo ('<div> You have no Permission Groups defined with the NPR API. </div>');
	}
}

 /**
 * 
 * create the select widget where the Id is the value in the array
 * @param  $field_name
 * @param  $keys - an array like (1=>'Value1', 2=>'Value2', 3=>'Value3');
 */
function ds_npr_show_post_types_select( $field_name, $keys ){
	
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
	
}

/**
 * 
 * create the select widget where the ID for an element is the index to the array
 * @param  $field_name
 * @param  $keys an array like (id1=>'Value1', id2=>'Value2', id3=>'Value3');
 */
function ds_npr_show_perms_select( $field_name, $keys ){
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
}
?>
