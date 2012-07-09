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
						//var_dump($audio);
						if (isset($audio->mp3)){
							if ($audio->mp3->type == 'mp3' && $audio->download->value == 'true' ){
								//var_dump('Got some audio');
			
	 	       			$metas[ NPR_AUDIO_META_KEY ][] = serialize( $story->audio->mp3->value );
							}
						}
        	}
        }
         

        if (isset($story->image[0])){
        	foreach ($story->image as $image){
        		$metas[NPR_IMAGE_GALLERY_META_KEY][] = serialize($image);
        	}
        }
        
        if ( $existing ) {
            $created = false;
            $args[ 'ID' ] = $existing->ID;
        }
        else {
            $created = true;
        }
        $id = wp_insert_post( $args );

        foreach ( $metas as $k => $v ) {
            update_post_meta( $id, $k, $v );
        }
		}
        return array( 'YES');
    }
}