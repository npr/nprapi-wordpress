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
  add_options_page('NPR API Get Multiple', 'NPR API Get Multi', 'manage_options',
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
    for($i = 0; $i < $num; $i++){
    	add_settings_field( 'ds_npr_query_'.$i, 'Query String '.$i, 'ds_npr_api_query_callback', 'ds_npr_api_get_multi_settings', 'ds_npr_api_get_multi_settings', $i );
    	register_setting( 'ds_npr_api_get_multi_settings', 'ds_npr_query_'.$i );
    }

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
	echo "<input type='text' value='$option' name='ds_npr_num' style='width: 30px;' />";
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


?>
