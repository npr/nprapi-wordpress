<?php
//require_once( 'get_stories_ui.php' );
 
require_once ('classes/NPRAPIWordpress.php');


function npr_push ( $post_ID, $post ) {
	//if the push url isn't set, don't even try to push.
	$push_url = get_option( 'ds_npr_api_push_url' );
	if (!empty ($push_url)){
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
		}
	}

}

function npr_delete ( $post_ID ) {
	$api_id_meta = get_post_meta($post_ID, NPR_STORY_ID_META_KEY);
	$api_id = $api_id_meta[0];
	$post = get_post($post_ID);
	//if the push url isn't set, don't even try to delete.
	$push_url = get_option( 'ds_npr_api_push_url' );
	if (!empty ($push_url) && !empty($api_id)){
		// For now, only submit regular posts, and only on publish.
		if ( $post->post_type != 'post' || $post->post_status != 'publish' ) {
			return;
		}
		$api = new NPRAPIWordpress();
		$retrieved = get_post_meta($post_ID, NPR_RETRIEVED_STORY_META_KEY, true);
		
		if (empty($retrieved) || $retrieved == 0){
			$api->send_delete($api_id);
		}
	}

}

//as far as I can tell, this is where the magic happens
add_action( 'save_post', 'npr_push', 10, 2 );
add_action( 'trash_post', 'npr_delete', 10, 2 );  
//this may need to check version and use 'wp_trash_post'
add_action( 'wp_trash_post', 'npr_delete', 10, 2 );
