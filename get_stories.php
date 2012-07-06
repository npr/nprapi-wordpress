<?php
require_once( 'get_stories_ui.php' );
 
require_once ('classes/NPRAPIWordpress.php');

 class DS_NPR_API {

	var $created_message = '';
	
    function load_page_hook() {
        if ( isset( $_POST ) && isset( $_POST[ 'story_id' ] ) ) {
            $story_id =  $_POST[ 'story_id' ] ;
            if (isset($_POST['publishNow'])){
            	$publish = true;
            }
            if (isset($_POST['createDaft'])){
            	$publish = false;
            }
        }
        else if ( isset( $_GET[ 'story_id' ]) && isset( $_GET[ 'create_draft' ]) ) {
            $story_id = $_GET[ 'story_id' ] ;
            
        }
        
        if ( isset( $story_id ) ) {

            // XXX: check that the API key is actually set
            //$api = new NPR_API_Client( get_option( 'ds_npr_api_key' ) );
            $api = new NPRAPIWordpress(); 
	          //check to see if we go an ID or a URL
		        if (is_numeric($story_id)){
					    if (strlen($story_id) >= 8){
					      $story_id = $story_id;
					    }
						} else {
							// url format: /yyyy/mm/dd/id
						  // url format: /blogs/name/yyyy/mm/dd/id
						  $story_id = preg_replace('/http\:\/\/[^\s\/]*npr\.org\/((([^\/]*\/){3,5})([0-9]{8,12}))\/.*/', '$4', $story_id);
						  if (!is_numeric($story_id)) {
						    // url format: /templates/story/story.php?storyId=id
						    $story_id = preg_replace('/http\:\/\/[^\s\/]*npr\.org\/([^&\s\<]*storyId\=([0-9]+)).*/', '$2', $story_id);
						  }
						}
            //var_dump($story_id);
            $params = array ('id' => $story_id, 'apiKey' => get_option( 'ds_npr_api_key' ));
            $api->request($params, 'query', get_option( 'ds_npr_api_pull_url' ));
            
            $api->parse();
            //var_dump($api);
            
            $story = $api->update_posts_from_stories($publish);
            if ( ! $story ) {
                // XXX: handle error
                return;
            }
        }
    }
    function DS_NPR_API() {
        if ( ! is_admin() ) {
            //add_action( 'the_content', array( &$this, 'embed_audio_clip' ) );
            return;
        }

        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        //add_action( 'load-posts_page_get-stories', array( &$this, 'load_page_hook' ) );
        add_action( 'load-posts_page_get-npr-stories', array( 'DS_NPR_API', 'load_page_hook' ) );
    }
	
    function admin_menu() {
        add_posts_page( 'Get NPR DS Stories', 'Get DS NPR Stories', 'edit_posts', 'get-npr-stories',   'ds_npr_get_stories' );
    }
}

new DS_NPR_API;