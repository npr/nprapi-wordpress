<?php

/**
 * as_nprml(): Translates a post to NPRML.  Returns an XML string.
 */
function as_nprml( $post ) {
    $story = post_to_nprml_story( $post );
    $doc = array();
    $doc[] = array(
        'tag' => 'list',
        'children' => array( array( 'tag' => 'story', 'children' => $story ), ),
    );
    $ret_xml = array_to_xml( 'nprml', array( 'version' => '0.93' ), $doc );
    
    return $ret_xml;
}

/**
 * 
 * Do the mapping from WP post to the array that we're going to build the NPRML from.  
 * This is also where we will do custom mapping if need be.
 * If a mapped custom field does not exist in a certain post, just send the default field.
 * @param  $post
 */
function post_to_nprml_story( $post ) {
    $story = array();
    $story[] = array( 
        'tag' => 'link',
        'attr' => array( 'type' => 'html' ),
        'text' => get_permalink( $post ),
    );
    $use_custom = get_option('dp_npr_push_use_custom_map');
    
    //get the list of metas available for this post
    $post_metas = get_post_custom_keys($post->ID);
    
    $teaser_text = '';
    if (!empty($post->post_excerpt)){
    	$teaser_text = $post->post_excerpt;
    }
    
    $custom_content_meta = get_option('ds_npr_api_mapping_body');
    if ($use_custom && !empty($custom_content_meta) && $custom_content_meta != '#NONE#' && in_array($custom_content_meta,$post_metas)){
    	$content = get_post_meta($post->ID, $custom_content_meta, true);
    	$post_for_teaser = $post;
    	$post_for_teaser->post_content = $content;
    	if (empty($teaser_text)){
	    	$teaser_text = nai_get_excerpt( $post_for_teaser );
    	}
    }
    else {
	    $content = $post->post_content ;
	    if (empty($teaser_text)){
		    $teaser_text = nai_get_excerpt( $post );
	    }
    }
    //lets see if there are any plugins that need to fix their shortcodes before we run do_shortcode
    if (has_filter('npr_ds_shortcode_filter')) {
	    $content = apply_filters('npr_ds_shortcode_filter', $content);
    }
	  //let any plugin that has short codes try and replace those with HTML
	  $content = do_shortcode( $content );
    //for any remaining short codes, nuke 'em
    $content = strip_shortcodes( $content );
    $content = apply_filters( 'the_content', $content );

    $story[] = array(
        'tag' => 'teaser',
        'text' => $teaser_text,
    );
    $custom_title_meta = get_option('ds_npr_api_mapping_title');
    if ($use_custom && !empty($custom_title_meta) && $custom_title_meta != '#NONE#' && in_array($custom_content_meta,$post_metas)){
    	$custom_title = get_post_meta($post->ID, $custom_title_meta, true);
    	$story[] = array(
	        'tag' => 'title',
	        'text' => $custom_title,
	    );
    }
    else {
	    $story[] = array(
	        'tag' => 'title',
	        'text' => $post->post_title,
	    );
    }
    
    /**
     * 
     *If there is a custom byline configured, send that.
     *If the site is using the coauthurs plugin, and get_coauthors exists, send the display names
     *
     *If no cool things are going on, just send the display name for the post_author field.
     *
     */
    $byline = FALSE;
    $custom_byline_meta = get_option('ds_npr_api_mapping_byline');
    if ($use_custom && !empty($custom_byline_meta) && $custom_byline_meta != '#NONE#' && in_array($custom_content_meta,$post_metas)){
 			$byline = TRUE;
    	$story[] = array(
	        'tag' => 'byline',
    			'children' => array ( array(
								'tag' => 'name',
	        			'text' => get_post_meta($post->ID, $custom_byline_meta, true),
    			)),
	    );
    }
    if (function_exists('get_coauthors')){
    	$coauthors = get_coauthors($post->ID);
    	if (!empty($coauthors)){
    		$byline = TRUE;   			
				foreach($coauthors as $i=>$co){
					$story[] = array(
	       		'tag' => 'byline',
						'children' => array ( array(
								'tag' => 'name',
	       										'text' => $co->display_name,
	   												)
	   									));
				}    			
    	}
    	else {
    		error_log('we do not have co authors');
    	}
   	}
   	else {
   		error_log('can not find get_coauthors');
   	}    
		if (!$byline){
			$story[] = array(
	        		'tag' => 'byline',
							'children' => array ( array('tag' => 'name',
		        		'text' => get_the_author_meta( 'display_name', $post->post_author ),
							)),
	    			);
    }

    // NPR One
    if (!empty($_POST['send_to_nprone'])) {
        $story[] = array(
            'tag' => 'parent',
            'attr' => array( 'id' => '319418027', 'type' => 'collection' ),
        );
    }

    #'miniTeaser' => array( 'text' => '' ),
    #'slug' => array( 'text' => '' ),
    $story[] = array(
        'tag' => 'storyDate',
        'text' => mysql2date( 'D, d M Y H:i:s +0000', $post->post_date_gmt ),
    );
    $story[] = array(
        'tag' => 'pubDate',
        'text' => mysql2date( 'D, d M Y H:i:s +0000', $post->post_modified_gmt ),
    );
    $story[] = array(
        'tag' => 'lastModifiedDate',
        'text' => mysql2date( 'D, d M Y H:i:s +0000', $post->post_modified_gmt ), 
    );
    $story[] = array(
        'tag' => 'partnerId',
        'text' => $post->guid,
    );
    //TODO:  When the API accepts sending both text and textWithHTML, send a totally bare text.  Don't do do_shortcode(). 
    //for now (using the npr story api) we can either send text or textWithHTML, not both.
    //it would be nice to send text after we strip all html and shortcodes, but we need the html
    //and sending both will duplicate the data in the API
    $story[] = array(
        'tag' => 'textWithHtml',
        'children' => split_paragraphs( $content ),
    );

    $perms_group = get_option('ds_npr_story_default_permission');
    if (!empty($perms_group)){
     	$story[] = array(
    			'tag' => 'permissions',
    			'children' => array ( array( 
    				'tag' => 'permGroup',
			     	'attr' => array( 'id' => $perms_group ),
    			)),
    	);
    }
    
    $custom_media_credit = get_option('ds_npr_api_mapping_media_credit');
		$custom_media_agency = get_option('ds_npr_api_mapping_media_agency');

		/* remove this for now until we decide if we're going to actually do this...km
		$dist_media_option = get_option('ds_npr_api_mapping_distribute_media');
		$dist_media_polarity = get_option('ds_npr_api_mapping_distribute_media_polarity');
    */
    $args = array(
			'order'=> 'DESC',
			'post_mime_type' => 'image',
			'post_parent' => $post->ID,
			'post_status' => null,
			'post_type' => 'attachment'
			);		
		$images = get_children( $args );
		$primary_image = get_post_thumbnail_id($post->ID);
			
		foreach ($images as $image){
			$custom_credit = '';
			$custom_agency = '';
			$image_metas = get_post_custom_keys($image->ID);
	    if ($use_custom && !empty($custom_media_credit) && $custom_media_credit != '#NONE#' && in_array($custom_media_credit,$image_metas)){
	    	$custom_credit = get_post_meta($image->ID, $custom_media_credit, true);
	    }
			if ($use_custom && !empty($custom_media_agency) && $custom_media_agency != '#NONE#' && in_array($custom_media_agency,$image_metas)){
	    	$custom_agency = get_post_meta($image->ID, $custom_media_agency, true);
	    }
	    
			if ($use_custom && !empty($dist_media_option) && $dist_media_option != '#NONE#' && in_array($dist_media_option,$image_metas)){
	    	$dist_media = get_post_meta($image->ID, $dist_media_option, true);
	    }

			//if the image field for distribute is set and polarity then send it.
			//all kinds of other math when polarity is negative or the field isn't set.
			$image_type = 'standard';
			if ($image->ID == $primary_image){
				$image_type = 'primary';
			}
			
			//is the image in the content?  If so, tell the APi with a flag that CorePublisher knows
			//WP may add something like "-150X150" to the end of the filename, before the extension.  Isn't that nice?
			$image_name_parts = split(".", $image_guid);
			$image_regex = "/". $image_name_parts[0] . "\-[a-zA-Z0-9]*" . $image_name_parts[1] . "/"; 
			$in_body = "";
			if (preg_match($image_regex, $content)) {
				if (strstr($image->guid, '?')){
					$in_body = "&origin=body";
				}
				else {
					$in_body = "?origin=body";
				}
			}
			$story[] = array( 
				'tag' => 'image',
				'attr' => array( 'src' => $image->guid . $in_body, 'type' => $image_type ), 
				'children' => array ( array(
						'tag' => 'title',
						'text' => $image->post_title,
						),
						array(
							'tag' => 'caption',
							'text' => $image->post_excerpt,
 						),
 						array(
 							'tag' => 'producer',  
 							'text' => $custom_credit
 						),
 						array(
 							'tag' => 'provider',  
 							'text' => $custom_agency
 						)
					),
			);
		}
			
		//should be able to do the same as image for audio, with post_mime_typ = 'audio' or something.
		$args = array(
			'order'=> 'DESC',
			'post_mime_type' => 'audio',
			'post_parent' => $post->ID,
			'post_status' => null,
			'post_type' => 'attachment'
		);		
		$audios = get_children( $args );
			
		foreach ($audios as $audio){
			$caption = $audio->post_excerpt;
			//if we don't have excerpt filled in, try content
			if (empty($caption)) {
				$caption = $audio->post_content;
			}
			
			$story[] = array(
      	'tag' => 'audio',
        'children' => array(
        	array(
          	'tag' => 'format',
            'children' => array ( array(
            	'tag' => 'mp3',
              'text' => $audio->guid,
            )),
          ),
          array(
            'tag' => 'description',
            'text' => $caption,
          ),
        ),
      );				
		}

		if ($enclosures = get_metadata('post', $post->ID, 'enclosure')) {
		  // This logic is specifically driven enclosure metadata items that are
		  // created by the PowerPress podcasting plug-in
		  foreach($enclosures as $enclosure) {
		    $pieces = explode("\n", $enclosure);

		    if (!empty($pieces[3])) {
		      $metadata = unserialize($pieces[3]);
		      $duration = (!empty($metadata['duration'])) ? nprapi_convert_duration($metadata['duration']) : NULL;
		    }
		    $story[] = array(
              'tag' => 'audio',
              'children' => array(
                array(
                  'tag' => 'duration',
                  'text' => (!empty($duration)) ? $duration : 0,
                ),
                array(
                  'tag' => 'format',
                  'children' => array(
                    array(
                      'tag' => 'mp3',
                      'text' => $pieces[0],
                    ),
                  ),
                ),
              ),
            );
		  }
		}
  return $story;
}

// Convert "HH:MM:SS" duration (not time) into seconds
function nprapi_convert_duration($duration) {
  $pieces = explode(':', $duration);
  $duration_in_seconds = ($pieces[0] * 60 * 60 + $pieces[1] * 60 + $pieces[2]);
  return $duration_in_seconds;
}

function split_paragraphs( $html ) {
    $parts = array_filter( 
        array_map( 'trim', preg_split( "/<\/?p>/", $html ) ) 
    );
    $graphs = array();
    $num = 1;
    foreach ( $parts as $part ) {
        $graphs[] = array( 
            'tag' => 'paragraph',
            'attr' => array( 'num' => $num ),
            'cdata' => $part,
        );
        $num++;
    }

    return $graphs;
}


/**
 * 
 */
function array_to_xml( $tag, $attrs, $data ) {
    $xml = new DOMDocument();
    $xml->formatOutput = true;
    
    $root = $xml->createElement( $tag );

    foreach ( $attrs as $k => $v ) {
        $root->setAttribute( $k, $v );
    }

    foreach ( $data as $item ) { 
        $elemxml = item_to_xml( $item, $xml );
        $root->appendChild( $elemxml );
    }
    
    $xml->appendChild( $root );

    return $xml->saveXML();
}


function item_to_xml( $item, $xml ) {
    if ( ! array_key_exists( 'tag', $item ) ) {
        error_log( "no tag for: " . print_r( $item, true ) );
    }
    $elem = $xml->createElement( $item[ 'tag' ] );
    if ( array_key_exists( 'children', $item ) ) {
        foreach ( $item[ 'children' ] as $child ) {
            $childxml = item_to_xml( $child, $xml );
            $elem->appendChild( $childxml );
        }
    }
    if ( array_key_exists( 'text', $item ) ) { 
        $elem->appendChild(
            $xml->createTextNode( $item[ 'text' ] )
        );
    }
    if ( array_key_exists( 'cdata', $item ) ) { 
        $elem->appendChild(
            $xml->createCDATASection( $item[ 'cdata' ] )
        );
    }
    if ( array_key_exists( 'attr', $item ) ) { 
        foreach ( $item[ 'attr' ] as $attr => $val ) {
            $elem->setAttribute( $attr, $val );
        }
    }
    return $elem;
}


/**
 * Retrieves the excerpt of any post.
 *
 * @param   object  $post       Post object
 * @param   int     $word_count Number of words (default 30)
 * @return  String
 */
function nai_get_excerpt( $post, $word_count = 30 ) {
    $text = $post->post_content;

    // HACK: This is ripped from wp_trim_excerpt() in 
    // wp-includes/formatting.php because there's seemingly no way to 
    // use it outside of The Loop
    // Filed as ticket #16372 in WP Trac.
    $text = strip_shortcodes( $text );

    $text = apply_filters( 'the_content', $text );
    $text = str_replace( ']]>', ']]&gt;', $text );
    $text = strip_tags( $text );
    $excerpt_length = apply_filters( 'excerpt_length', $word_count );
    //$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[...]' );
    $words = preg_split( "/[\n\r\t ]+/", $text, $excerpt_length + 1, 
                         PREG_SPLIT_NO_EMPTY );
    if ( count( $words ) > $excerpt_length ) {
        array_pop( $words );
        $text = implode( ' ', $words );
        //$text = $text . $excerpt_more;
    } else {
        $text = implode( ' ', $words );
    }

    return $text;
}

?>
