<?php

/**
 * @file
 *
 * Defines a class for NPRML creation/transmission and retreival/parsing
 * Unlike NPRAPI class, NPRAPIDrupal is drupal-specific
 */
require_once ('NPRAPI.php');

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

    $response = wp_remote_get( $request_url );
    //drupal_http_request($request_url, array('method' => $this->request->method, 'data' => $this->request->data));
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
  
	function update_posts_from_stories($publish = TRUE ) {
		foreach ($this->stories as $story) { 
        $exists = new WP_Query( array( 'meta_key' => NPR_STORY_ID_META_KEY, 
                                       'meta_value' => $story->id ) );
        if ( $exists->post_count ) {
            // XXX: might be more than one here;
            $existing = $exists->post;
        }
        else {
            $existing = null;
        }
        
        //var_dump($story->title);

        $args = array(
            'post_title'   => $story->title,
            'post_excerpt' => $story->teaser,
            'post_content' => $story->body,
        );
        if ( ! $existing ) {
        	if ($publish){
            $args['post_status'] = 'publish';
        	}
        	else {
        		$args['post_status'] = 'draft';
        	}
        }

        $by_line = '';
        if (isset($story->byline->name->value)){
        	$by_line = $story->byline->name->value;
        }
        $metas = array(
            NPR_STORY_ID_META_KEY      => $story->id,
            NPR_API_LINK_META_KEY      => $story->link['api']->value,
            NPR_HTML_LINK_META_KEY     => $story->link['html']->value,
            NPR_SHORT_LINK_META_KEY    => $story->link['short']->value,
            NPR_STORY_CONTENT_META_KEY => $story->body,
            NPR_BYLINE_META_KEY        => $by_line,
        );
        
        //this doesn't work just yet.  Parse isn't getting audio correctly.
        if ( isset($story->audio) ) {
        	foreach ($story->audio as $audio){
        		//var_dump($audio->format->mp3['mp3']);
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
        		//$image_url = media_sideload_image($image->src, $post_id, $image->title->value);
        		//var_dump($image_url);
        		
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
		}
        return array( 'YES');
    }
}