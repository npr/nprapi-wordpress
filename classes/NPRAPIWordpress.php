<?php

/**
 * @file
 *
 * Defines a class for NPRML creation/transmission and retreival/parsing
 * Unlike NPRAPI class, NPRAPIDrupal is drupal-specific
 */
require_once ('NPRAPI.php');
require_once ('nprml.php');

class NPRAPIWordpress extends NPRAPI {

  /**
   * Makes HTTP request to NPR API.
   *
   * @param array $params
   *   Key/value pairs to be sent (within the request's query string).
   *
   *
   * @param string $path
   *   The path part of the request URL (i.e., http://example.com/PATH).
   *
   * @param string $base
   *   The base URL of the request (i.e., HTTP://EXAMPLE.COM/path) with no trailing slash.
   */
  function request($params = array(), $path = 'query', $base = self::NPRAPI_PULL_URL) {

    $this->request->params = $params;
    $this->request->path = $path;
    $this->request->base = $base;

    $queries = array();
    foreach ($this->request->params as $k => $v) {
      $queries[] = "$k=$v";
    }
    $request_url = $this->request->base . '/' . $this->request->path . '?' . implode('&', $queries);
    $this->request->request_url = $request_url;

    $this->query_by_url($request_url);

  }
  
  /**
   * 
   * Query a single url.  If there is not an API Key in the query string, append one, but otherwise just do a straight query
   * 
   * @param string $url -- the full url to query.
   */
  function query_by_url($url){
  	//check to see if the API key is included, if not, add the one from the options
  	if (!stristr($url, 'apiKey=')){
  		$url .= '&apiKey='. get_option( 'ds_npr_api_key' );
  	}
  	
  	$this->request->request_url = $url;

    $response = wp_remote_get( $url );
    if( !is_wp_error( $response ) ) {
	    $this->response = $response;
	    	
	    if ($response['response']['code'] == self::NPRAPI_STATUS_OK) {
	    	
	      if ($response['body']) {
	        $this->xml = $response['body'];
	      }
	      else {
	        $this->notice[] = t('No data available.');
	      }
	    }
    }
    else {
    	echo ('Error retrieving story for url='.$url);
    }
  }
  
	function update_posts_from_stories($publish = TRUE ) {
		if (!emptry($this->stories)){
			foreach ($this->stories as $story) { 
				
	        $exists = new WP_Query( array( 'meta_key' => NPR_STORY_ID_META_KEY, 
	                                       'meta_value' => $story->id ) );
	        $post_mod_date = 0;
	        if ( $exists->post_count ) {
	            // XXX: might be more than one here;
	            $existing = $exists->post;
	            $existing_status = $exists->posts[0]->post_status;
	            $post_mod_date_meta = get_post_meta($existing->ID, NPR_LAST_MODIFIED_DATE_KEY);
	            if (!empty($post_mod_date_meta[0])){
		            $post_mod_date = strtotime($post_mod_date_meta[0]);
	            }
	        }
	        else {
	            $existing = null;
	        }
	        
	        //set the story as draft, so we don't try ingesting it
	        $args = array(
	            'post_title'   => $story->title,
	            'post_excerpt' => $story->teaser,
	            'post_content' => $story->body,
	        		'post_status'  => 'draft'
	        );
					//check the last modified date, if the story hasn't changed, just go on
					if ($post_mod_date != strtotime($story->lastModifiedDate->value)) {
		        $by_line = '';
		        if (isset($story->byline->name->value)){
		        	$by_line = $story->byline->name->value;
		        }
		        
		        //set the meta RETRIEVED so when we publish the post, we dont' try ingesting it
		        $metas = array(
		            NPR_STORY_ID_META_KEY      => $story->id,
		            NPR_API_LINK_META_KEY      => $story->link['api']->value,
		            NPR_HTML_LINK_META_KEY     => $story->link['html']->value,
		            //NPR_SHORT_LINK_META_KEY    => $story->link['short']->value,
		            NPR_STORY_CONTENT_META_KEY => $story->body,
		            NPR_BYLINE_META_KEY        => $by_line,
		            NPR_RETRIEVED_STORY_META_KEY => 1,
		            NPR_PUB_DATE_META_KEY => $story->pubDate->value,
		            NPR_STORY_DATE_MEATA_KEY => $story->storyDate->value,
								NPR_LAST_MODIFIED_DATE_KEY=> $story->lastModifiedDate->value,
		        );
		        //get audio
		        if ( isset($story->audio) ) {
		        	foreach ($story->audio as $audio){
								if (isset($audio->format->mp3['mp3'])){
									if ($audio->format->mp3['mp3']->type == 'mp3' && $audio->permissions->download->allow == 'true' ){	
			 	       			$metas[NPR_AUDIO_META_KEY][] =  $audio->format->mp3['mp3']->value;
									}
								}
		        	}
		        }
		        
		        if ( $existing ) {
		            $created = false;
		            $args[ 'ID' ] = $existing->ID;
		        }
		        else {
		            $created = true;
		        }
		        $post_id = wp_insert_post( $args );
		
		        //now that we have an id, we can add images
		        //this is the way WP seems to do it, but we couldn't call media_sideload_image or media_ because that returned only the URL
		        //for the attachment, and we want to be able to set the primary image, so we had to use this method to get the attachment ID.
						if (isset($story->image[0])){
		        	foreach ($story->image as $image){
		        		
		        		// Download file to temp location
		            $tmp = download_url( $image->src );
		            // Set variables for storage
		            // fix file filename for query strings
		            preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $image->src, $matches);
		            $file_array['name'] = basename($matches[0]);
		            $file_array['tmp_name'] = $tmp;
		
		            // If error storing temporarily, unlink
		            if ( is_wp_error( $tmp ) ) {
		            	@unlink($file_array['tmp_name']);
		              $file_array['tmp_name'] = '';
		            }
		
		            // do the validation and storage stuff
		            $id = media_handle_sideload( $file_array, $post_id, $image->title->value );
		            // If error storing permanently, unlink
		            if ( is_wp_error($id) ) {
		            	@unlink($file_array['tmp_name']);
		            }
		
		            //set the primary image
		            if ($image->type == 'primary'){
		            	add_post_meta($post_id, '_thumbnail_id', $id, true);
		            }
		        		
		        	}
		        }
		        foreach ( $metas as $k => $v ) {
		            update_post_meta( $post_id, $k, $v );
		        }
		       
		        $args = array(
		        		'post_title'   => $story->title,
		            'post_content' => $story->body,
		        		'post_excerpt' => $story->teaser,    
		            'ID'   => $post_id,
		        );
					 //now set the status
						if ( ! $existing ) {
		        	if ($publish){
		            $args['post_status'] = 'publish';
		        	}
		        	else {
		        		$args['post_status'] = 'draft';
		        	}
		        }
		        else {
		        	//if the post existed, save its status
		        	$args['post_status'] = $existing_status;
		        }
		        $ret = wp_insert_post( $args );
					}
			}
		}
        return array( 'YES');
    }



  /**
   * Create NPRML from wordpress post.
   *
   * @param object $post
   *   A wordpress post.
   *
   * @return string
   *   An NPRML string.
   */
  function create_NPRML($post) {
		//using some old helper code
		return as_nprml($post);
  }

  function send_request ($nprml, $post_ID) {

    $url = add_query_arg( array( 
        'orgId'  => get_option( 'ds_npr_api_org_id' ),
        'apiKey' => get_option( 'ds_npr_api_key' )
    ), get_option( 'ds_npr_api_push_url' ) . '/story' );

    $result = wp_remote_post( $url, array( 'body' => $nprml ) );
    $body = wp_remote_retrieve_body( $result );
    if ( $body ) {
        $response_xml = simplexml_load_string( $body );
        $npr_story_id = (string) $response_xml->list->story['id'];
        update_post_meta( $post_ID, NPR_STORY_ID_META_KEY, $npr_story_id );
    }
    else {
        error_log( 'INGEST ERROR: ' . print_r( $result, true ) );
    }
  }

  function send_delete($api_id){
  	
  	$url = add_query_arg( array( 
        'orgId'  => get_option( 'ds_npr_api_org_id' ),
        'apiKey' => get_option( 'ds_npr_api_key' ),
  			'id' => $api_id
    ), get_option( 'ds_npr_api_push_url' ) . '/story' );
		//wp doesn't let me do a wp_remote_post with method=DELETE so we have to make our own curl request.  fun
		//a lot of this code came from WP's class-http object
		//$result = wp_remote_post( $url, array( 'method' => 'DELETE' ) );
		$handle = curl_init();
		curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, 'DELETE' );
	  curl_setopt( $handle, CURLOPT_URL, $url);
	  curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_exec( $handle );
		curl_close( $handle );
		
  }

}
