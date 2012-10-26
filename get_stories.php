<?php
require_once( 'get_stories_ui.php' );
 
require_once ('classes/NPRAPIWordpress.php');


/**
register_activation_hook(DS_NPR_PLUGIN_FILE, array ('DS_NPR_API','ds_npr_story_activation'));
add_action('npr_ds_hourly_cron', array ('DS_NPR_API','ds_npr_story_cron_pull'));
register_deactivation_hook(DS_NPR_PLUGIN_FILE, array ('DS_NPR_API','ds_npr_story_deactivation'));
**/
		
 class DS_NPR_API {

	var $created_message = '';

	public static function ds_npr_story_cron_pull() {
		// here we should get the list of IDs/full urls that need to be checked hourly

		//because this is run on cron, and may be fired off by an non-admin, we need to load a bunch of stuff
		require_once (WP_PLUGIN_DIR.'/../../wp-admin/includes/file.php');
		require_once (WP_PLUGIN_DIR.'/../../wp-admin/includes/media.php');
		require_once (WP_PLUGIN_DIR.'/../../wp-admin/includes/admin.php');
		require_once (WP_PLUGIN_DIR.'/../../wp-load.php');
		require_once (WP_PLUGIN_DIR.'/../../wp-includes/class-wp-error.php');
		
		//here we go.
		$num =  get_option('ds_npr_num');

		for ($i=0; $i<$num; $i++){
			$api = new NPRAPIWordpress(); 
			$q = 'ds_npr_query_'.$i;
			$query_string = get_option($q);
			error_log('querying for '. $query_string);
			//if the query string contains the pull url and 'query', just make request from the API
			if (stristr($query_string, get_option('ds_npr_api_pull_url')) && stristr($query_string,'query')){
				$api->query_by_url($query_string);
			}
			//if the string doesn't contain the base url, try to query using an ID
			else{
				$params = array ('id' => $query_string, 'apiKey' => get_option( 'ds_npr_api_key' ));
        $api->request($params, 'query', get_option( 'ds_npr_api_pull_url' ));
			}
			$api->parse();
      //var_dump($api);
      try {
	      if (empty($api->message) || ($api->message->level != 'warning')){
	      	$story = $api->update_posts_from_stories();
	      }
	      else {
		    	if ( empty($story) ) {
		          error_log('Not going to save story.  Return from query='. $query_string .', we got an error='.$api->message->id. ' error');
		      }
	      }
      } catch (Exception $e){
      	error_log('we have an error going in '. __FUNCTION__. ' like this :'. $e);
      }
		}
	}
   
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
            $api = new NPRAPIWordpress(); 
	          //check to see if we got an ID or a URL
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
            $params = array ('id' => $story_id, 'apiKey' => get_option( 'ds_npr_api_key' ));
            $api->request($params, 'query', get_option( 'ds_npr_api_pull_url' ));
            
            $api->parse();
            
            if (empty($api->message) || ($api->message->level != 'warning')){
            	$story = $api->update_posts_from_stories($publish);
            }
            else {
	            if ( empty($story) ) {
	                error_log('Not going to save the return from query for '. $story_id .', we got an error='.$api->message->id. ' error');
	                return;
	            }
            }
        }
    }
    function DS_NPR_API() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'load-posts_page_get-npr-stories', array( 'DS_NPR_API', 'load_page_hook' ) );
    }
	
    function admin_menu() {
        add_posts_page( 'Get NPR DS Stories', 'Get DS NPR Stories', 'edit_posts', 'get-npr-stories',   'ds_npr_get_stories' );
    }
    
}

new DS_NPR_API;