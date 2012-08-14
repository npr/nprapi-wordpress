<?php
//require_once( 'get_stories_ui.php' );
 
require_once ('classes/NPRAPIWordpress.php');


function npr_push ( $post_ID, $post ) {

	// For now, only submit regular posts, and only on publish.
	if ( $post->post_type != 'post' || $post->post_status != 'publish' ) {
		return;
	}
	$api = new NPRAPIWordpress();

	error_log('trying to push a post (' . $post_ID . ') to api\n\n');
	$api->send_request($api->create_NPRML($post), $post_ID);
}

//as far as I can tell, this is where the magic happens
add_action( 'save_post', 'npr_push', 10, 2 );
