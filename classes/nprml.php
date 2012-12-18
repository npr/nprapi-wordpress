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
    
    $custom_content_meta = get_option('ds_npr_api_mapping_body');
    if ($use_custom && !empty($custom_content_meta) && $custom_content_meta != '#NONE#' && in_array($custom_content_meta,$post_metas)){
    	$content = get_post_meta($post->ID, $custom_content_meta, true);
    	$post_for_teaser = $post;
    	$post_for_teaser->post_content = $content;
    	$teaser_text = nai_get_excerpt( $post_for_teaser );
    }
    else {
	    $content = strip_shortcodes( $post->post_content );
	    $teaser_text = nai_get_excerpt( $post );
    }
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
    
    $custom_byline_meta = get_option('ds_npr_api_mapping_byline');
    if ($use_custom && !empty($custom_byline_meta) && $custom_byline_meta != '#NONE#' && in_array($custom_content_meta,$post_metas)){
    	$custom_byline = get_post_meta($post->ID, $custom_byline_meta, true);
    	$story[] = array(
	        'tag' => 'byline',
	        'text' => $custom_byline,
	    );
    }
    else {
	    $story[] = array(
	        'tag' => 'byline',
	        'text' => get_the_author_meta( 'display_name', $post->post_author ),
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
    				'text' => $perms_group
    			)),
    	);
    }
    $args = array(
			'numberposts' => 1,
			'order'=> 'ASC',
			'post_mime_type' => 'image',
			'post_parent' => $post->ID,
			'post_status' => null,
			'post_type' => 'attachment'
			);		
			$images = get_children( $args );
			$primary_image = get_post_thumbnail_id($post->ID);
			
			foreach ($images as $image){
				$image_type = 'standard';
				if ($image->ID == $primary_image){
					$image_type = 'primary';
				}
				$story[] = array( 
					'tag' => 'image',
					'attr' => array( 'src' => $image->guid, 'type' => $image_type ), 
					'children' => array ( array(
							'tag' => 'title',
							'text' => $image->post_title,
							),
							array(
								'tag' => 'caption',
								'text' => $image->post_excerpt,
 					)
						),
				);
			}
			
			//should be able to do the same as image for audio, with post_mime_typ = 'audio' or something.
			$args = array(
			'numberposts' => 1,
			'order'=> 'ASC',
			'post_mime_type' => 'audio',
			'post_parent' => $post->ID,
			'post_status' => null,
			'post_type' => 'attachment'
			);		
			$audios = get_children( $args );
			
			foreach ($audios as $audio){
				$caption = $audio->post_excerpt;
				$description = $audio->post_content;
				$story[] = array( 
					'tag' => 'audio',
					'children' => array( array(
							'tag' => 'format',
							'children' => array ( array(
									'tag' => 'mp3',
									'text' => $audio->guid,
							)),
						)),
						'description' => $description, 
				);
				
			}
    return $story;
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
