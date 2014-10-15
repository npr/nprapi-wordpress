<?php
// add don't pull button to links under title
add_filter( 'post_row_actions', 'dontpull_row_actions', 10, 2 );
function dontpull_row_actions( $actions, WP_Post $post ) {
	$npr_story_id = get_post_meta( $post->ID, 'npr_story_id', true );
	$pull_post_type = get_option('ds_npr_pull_post_type');;
    if ( $post->post_type != $pull_post_type || $npr_story_id == '' ) { //return default actions if 
        return $actions;
    }
    
    $actions['dontpull'] = "<a class='blacklist_story'  data-storyid='" . $npr_story_id . "' title='" . esc_attr( __( 'This story will NOT be pulled again)') ) . "' href='" . get_delete_post_link( $post->ID,"", true ) . "'>" . __( 'Delete Permanently/Don&#8217;t Pull' ) . "</a>";
    return $actions;
}

//ajax_object & event handler script load
add_action( 'admin_enqueue_scripts', 'enqueue_manager' );
function enqueue_manager($hook) {       
	wp_enqueue_script( 'ajax-script', plugins_url( '/publish_manager.js', __FILE__ ), array('jquery'));

	// in javascript, object properties are accessed as ajax_object.ajax_url
	wp_localize_script( 'ajax-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

//handler function to process blacklist/whitelist requests
add_action('wp_ajax_blacklist', 'blacklist_callback');
add_action('wp_ajax_whitelist', 'blacklist_callback');
function blacklist_callback() {
	global $wpdb;
	$number_to_block = $_POST['storyId'];
	$action = $_POST['action'];
	$table_name = $wpdb->prefix . "ds_npr_dont_pull_list";
	global $wpdb;
	 if ($action == 'blacklist'){
		 $rows_affected = $wpdb->insert( $table_name, array( 'story_id' => $number_to_block) );
	 }
	else if ($action == 'whitelist'){
		$rows_affected = is_numeric($number_to_block) ? $wpdb->delete( $table_name, array( 'story_id' => $number_to_block), array( '%s' )) : 0;

	}
	echo $rows_affected;
	die();
}




