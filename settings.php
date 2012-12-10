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
	$num =  get_option('ds_npr_num');
	if (empty($num)){
		$num = 1;
	}
	$k = $num;
  $opt = get_option('ds_npr_query_'.$k);
  while ($k < NPR_MAX_QUERIES) {
  	delete_option('ds_npr_query_'.$k);
  	$k++;
  	$opt = get_option('ds_npr_query_'.$k);
  }

	//make sure we remove any queries we didn't want to use
	if (!empty($num) && $num < NPR_MAX_QUERIES){
		$k = $num;
	  $opt = get_option('ds_npr_query_'.$k);
	  while ($k < NPR_MAX_QUERIES) {
	  	delete_option('ds_npr_query_'.$k);
	  	$k++;
	  }
	}
  add_options_page('Auto Fetch from the NPR API settings', 'NPR API Get Multi', 'manage_options',
									 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_options_page');
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

    add_settings_field( 'ds_npr_num', 'Number of things to get', 'ds_npr_api_num_multi_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings' );
    register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_num' );
		$num =  get_option('ds_npr_num');

		if (empty($num)){
			$num = 5;
		}
    for($i = 0; $i < $num; $i++){
    	add_settings_field( 'ds_npr_query_'.$i, 'Query String '.$i, 'ds_npr_api_query_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
    	register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_'.$i );
    }
    
    add_settings_field( 'ds_npr_pull_post_type', 'NPR Pull Post Type', 'ds_npr_pull_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_pull_post_type' );
    
    add_settings_field( 'ds_npr_push_post_type', 'NPR Push Post Type', 'ds_npr_push_post_type_callback', 'ds_npr_api', 'ds_npr_api_settings' );
    register_setting( 'ds_npr_api', 'ds_npr_push_post_type' );

}
add_action( 'admin_init', 'ds_npr_settings_init' );

function ds_npr_api_settings_callback() { }

function ds_npr_api_get_multi_settings_callback() { 
	 
}

function ds_npr_api_query_callback($i){
	$option = get_option('ds_npr_query_'.$i);
	$name = 'ds_npr_query_'.$i;
	echo "<input type='text' value='$option' name='$name' style='width: 300px;' />";
}
function ds_npr_api_num_multi_callback(){
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
	ds_npr_show_post_types_select('ds_npr_pull_post_type', $post_types);
}

function ds_npr_push_post_type_callback() {
	$post_types = get_post_types();
	ds_npr_show_post_types_select('ds_npr_push_post_type', $post_types);
		echo ('<div> If you change the Push Post Type setting remember to update the mappings for API Fields at <a href="' . admin_url('options-general.php?page=ds_npr_api_push_mapping') . '">NPR API Field Mapping </a> page.</div>');
	
}

 /**
 * 
 * create the select widget of all meta fields
 * @param  $field_name
 * @param  $keys
 */
function ds_npr_show_post_types_select($field_name, $keys){
	
	$selected = get_option($field_name);
	
	echo "<div><select id=" . $field_name . " name=" . $field_name . ">";
	
	echo '<option value=""> &mdash; Select &mdash; </option>'; 
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
?>
