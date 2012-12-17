<?php
 
require_once ('classes/NPRAPIWordpress.php');

/**
 * 
 * push the contents and fields for a post to the NPR API
 * @param unknown_type $post_ID
 * @param unknown_type $post
 */
function npr_push ( $post_ID, $post ) {
	$push_post_type = get_option('ds_npr_push_post_type');
	if (empty($push_post_type)){
		$push_post_type = 'post';
	}
	
	
	//if the push url isn't set, don't even try to push.
	$push_url = get_option( 'ds_npr_api_push_url' );

	if (!empty ($push_url)){
		// For now, only submit regular posts, and only on publish.
		if ( $post->post_type != $push_post_type || $post->post_status != 'publish' ) {
			return;
		}
		if (empty($post->post_content)){
			update_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, 'Body is required for a post to be pushed to the NPR API.' );
			return;
		}
		else {
			delete_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, 'Body is required for a post to be pushed to the NPR API.' );
		}
		$api = new NPRAPIWordpress();
		$retrieved = get_post_meta($post_ID, NPR_RETRIEVED_STORY_META_KEY, true);
		
		if (empty($retrieved) || $retrieved == 0){
			$api->send_request($api->create_NPRML($post), $post_ID);
		}
		else {
			//error_log('Not pushing the story because it came from the API');
		}
	}

}

/**
 * 
 * Inform the NPR API that a post needs to be deleted.
 * @param unknown_type $post_ID
 */
function npr_delete ( $post_ID ) {
	$push_post_type = get_option('ds_npr_push_post_type');
	if (empty($push_post_type)){
		$push_post_type = 'post';
	}
	
	$api_id_meta = get_post_meta($post_ID, NPR_STORY_ID_META_KEY);
	$api_id = $api_id_meta[0];
	$post = get_post($post_ID);
	//if the push url isn't set, don't even try to delete.
	$push_url = get_option( 'ds_npr_api_push_url' );
	if ($post->post_type == $push_post_type && !empty ($push_url) && !empty($api_id)){
		// For now, only submit regular posts, and only on publish.
		if ( $post->post_type != 'post' || $post->post_status != 'publish' ) {
			return;
		}
		$api = new NPRAPIWordpress();
		$retrieved = get_post_meta($post_ID, NPR_RETRIEVED_STORY_META_KEY, true);
		
		if (empty($retrieved) || $retrieved == 0){
			$api->send_request($api->create_NPRML($post), $post_ID);
		}
		else {
			//error_log('Not pushing the story because it came from the API');
			$api->send_delete($api_id);
		}
	}

}

//as far as I can tell, this is where the magic happens
add_action( 'save_post', 'npr_push', 10, 2 );
add_action( 'trash_post', 'npr_delete', 10, 2 );  
//this may need to check version and use 'wp_trash_post'
add_action( 'wp_trash_post', 'npr_delete', 10, 2 );

/**
 * 
 * define the option page for mapping fields
 */
function ds_npr_push_add_field_mapping_page() {
    add_options_page( 'NPR API Push Field Mapping', 'NPR API Field Mapping', 'manage_options',
                      'ds_npr_api_push_mapping', 'ds_npr_add_field_mapping_page' );
    
}

add_action( 'admin_menu', 'ds_npr_push_add_field_mapping_page' );

/**
 * 
 * Callback for push mapping page
 */
function ds_npr_api_push_mapping_callback() { }


/**
 * 
 * Query the database for any meta fields for a post type, then store that in a WP transient/cache for a day.
 * I don't see the need for this cache to be any shorter, there's not a lot of adding of meta keys happening.
 * To clear this cache, after adding meta keys, you need to run delete_transient('ds_npr_' .  $post_type.'_meta_keys')
 * @param  $post_type
 */
function ds_npr_push_meta_keys($post_type = 'post'){
	
  global $wpdb;
  $limit = (int) apply_filters( 'postmeta_form_limit', 30 );

  $query = "
        SELECT DISTINCT($wpdb->postmeta.meta_key) 
        FROM $wpdb->posts 
        LEFT JOIN $wpdb->postmeta 
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
        WHERE $wpdb->posts.post_type = '%s' 
        AND $wpdb->postmeta.meta_key != '' 
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
    ";
  $keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
	if ( $keys )
		natcasesort($keys);

  //set_transient('ds_npr_' .  $post_type .'_meta_keys', $keys, 60*60*24); # 1 Day Expiration
  return $keys;
}

/**
 * 
 * get the meta keys for a post type, they could be stored in a cache.
 * 
 * @param  $post_type default is 'post'
 */
function ds_npr_get_post_meta_keys($post_type = 'post'){
    //$cache = get_transient('ds_npr_' .  $post_type .'_meta_keys');
    if (!empty($cache)){
    	$meta_keys = $cache;
    }
    else {
    	$meta_keys = ds_npr_push_meta_keys($post_type);
    }
    return $meta_keys;
}

/**
  Set up the fields for mapping custom meta fields to NRPML fields that we push to the API
*/
function ds_npr_push_settings_init() {
    add_settings_section( 'ds_npr_push_settings', 'NPR API PUSH settings', 'ds_npr_api_push_settings_callback', 'ds_npr_api_push_mapping' );
    
    add_settings_field( 'dp_npr_push_use_custom_map', 'Use Custom Settings', 'ds_npr_api_use_custom_mapping_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'dp_npr_push_use_custom_map' );
    
    add_settings_field( 'ds_npr_api_mapping_title', 'Story Title', 'ds_npr_api_mapping_title_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_title' );
    
    add_settings_field( 'ds_npr_api_mapping_body', 'Story Body', 'ds_npr_api_mapping_body_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_body' );
    
    add_settings_field( 'ds_npr_api_mapping_byline', 'Story Byline', 'ds_npr_api_mapping_byline_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_byline' );

}
add_action( 'admin_init', 'ds_npr_push_settings_init' );

/**
 * call back for push settings
 */
function ds_npr_api_push_settings_callback() {
}

/**
 * callback for use custom mapping checkbox
 */
function ds_npr_api_use_custom_mapping_callback(){
	$use_custom = get_option('dp_npr_push_use_custom_map');
	$check_box_string = "<input id='dp_npr_push_use_custom_map' name='dp_npr_push_use_custom_map' type='checkbox' value='true'";

	if ($use_custom){
		$check_box_string .= ' checked="checked" ';
	}
	$check_box_string .= "/>";

	echo $check_box_string;
}

/**
 * callback for title mapping
 */
function ds_npr_api_mapping_title_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	
	$keys = ds_npr_get_post_meta_keys($push_post_type);
	ds_npr_show_keys_select('ds_npr_api_mapping_title', $keys);
}

/**
 * callback for body mapping
 */
function ds_npr_api_mapping_body_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	$keys = ds_npr_get_post_meta_keys($push_post_type);
	ds_npr_show_keys_select('ds_npr_api_mapping_body', $keys);
}

/**
 * callback for byline mapping
 */
function ds_npr_api_mapping_byline_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	$keys = ds_npr_get_post_meta_keys($push_post_type);
	ds_npr_show_keys_select('ds_npr_api_mapping_byline', $keys);
}

/**
 * 
 * create the select widget of all meta fields
 * @param  $field_name
 * @param  $keys
 */
function ds_npr_show_keys_select($field_name, $keys){
	
	$selected = get_option($field_name);
	
	echo "<div><select id=" . $field_name . " name=" . $field_name . ">";
	
	echo '<option value="#NONE#"> &mdash; default &mdash; </option>'; 
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

function ds_npr_get_push_post_type() {
	$push_post_type = get_option('ds_npr_push_post_type');
	if (empty($push_post_type)){
		$push_post_type = 'post';
	}
	return $push_post_type;
}

function ds_npr_get_permission_groups(){
	
$perm_groups = '';
	//query the API for the lists for this org.
	$perm_url = get_option('ds_npr_api_push_url') . '/orgs/' . get_option('ds_npr_api_org_id') . '/groups'.'?apiKey='. get_option('ds_npr_api_key');;
	$http_result = wp_remote_get($perm_url);
	if( !is_wp_error( $http_result ) ) {
		$perm_groups_objs = json_decode($http_result['body']);
		foreach($perm_groups_objs as $pg){
			$perm_groups[$pg->group_id]['name'] = $pg->name;
		}
	}
	else {
		$perm_groups = -1;
	}
	//var_dump($perm_groups);
	//exit;
	return $perm_groups;
}
