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
    return array_to_xml( 'nprml', array( 'version' => '0.93' ), $doc );
}

function post_to_nprml_story( $post ) {
    $content = strip_shortcodes( $post->post_content );
    $content = apply_filters( 'the_content', $content );
    $story = array();
    $story[] = array( 
        'tag' => 'link',
        'attr' => array( 'type' => 'html' ),
        'text' => get_permalink( $post ),
    );
    $story[] = array(
        'tag' => 'title',
        'text' => $post->post_title,
    );
    $story[] = array(
        'tag' => 'teaser',
        'text' => nai_get_excerpt( $post ),
    );
    $story[] = array(
        'tag' => 'byline',
        'text' => get_the_author_meta( 'display_name', $post->post_author ),
    );
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

    $args = array(
			'numberposts' => 1,
			'order'=> 'ASC',
			'post_mime_type' => 'image',
			'post_parent' => $post->ID,
			'post_status' => null,
			'post_type' => 'attachment'
			);		
			$images = get_children( $args );
			
			foreach ($images as $image){
				$story[] = array( 
					'tag' => 'image',
					'attr' => array( 'src' => $image->guid ), 
					'children' => array ( array(
							'tag' => 'title',
							'text' => $image->post_title,
					)),
					'children' => array ( array(
						'tag' => 'caption',
						'text' => $image->post_excerpt,
 					)),
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
    // XXX: for testing purposes
    //$parts[] = 'From ' . get_bloginfo( 'name' ) . ', part of the Argo Network.';
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
