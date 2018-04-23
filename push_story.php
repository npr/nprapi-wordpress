<?php
/**
 * Functions relating to pushing content to the NPR API
 */

require_once ( NPRSTORY_PLUGIN_DIR . 'classes/NPRAPIWordpress.php' );

/**
 * push the contents and fields for a post to the NPR API
 *
 * Limited to users that can publish posts
 *
 * @param Int $post_ID
 * @param WP_Post $post
 */
function nprstory_api_push ( $post_ID, $post ) {
	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die(
			__( 'You do not have permission to publish posts, and therefore you do not have permission to push posts to the NPR API.', 'nprapi' ),
			__( 'NPR Story API Error', 'nprapi' ),
			403
		);
	}

	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty( $push_post_type ) ) {
		$push_post_type = 'post';
	}

	//if the push url isn't set, don't even try to push.
	$push_url = get_option( 'ds_npr_api_push_url' );

	if ( ! empty ( $push_url ) ) {
		// For now, only submit the sort of post that is the push post type, and then only if published
		if ( $post->post_type != $push_post_type || $post->post_status != 'publish' ) {
			return;
		}

		/*
		 * If there's a custom mapping for the post content,
		 * use that content instead of the post's post_content
		 */
		$content = $post->post_content;
		$use_custom = get_option( 'dp_npr_push_use_custom_map' );
		$body_field = 'Body';
		if ( $use_custom ) {
			// Get the list of post meta keys available for this post.
			$post_metas = get_post_custom_keys( $post->ID );

			$custom_content_meta = get_option( 'ds_npr_api_mapping_body' );
			$body_field = $custom_content_meta;
				if ( ! empty( $custom_content_meta ) && $custom_content_meta !== '#NONE#' && in_array( $custom_content_meta, $post_metas, true ) ) {
				$content = get_post_meta( $post->ID, $custom_content_meta, true );
			}
		}

		// Abort pushing to NPR if the post has no content
		if ( empty( $content ) ) {
			update_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, esc_html( $body_field ) . ' is required for a post to be pushed to the NPR API.' );
			return;
		} else {
			delete_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, esc_html( $body_field ) . ' is required for a post to be pushed to the NPR API.' );
		}

		$api = new NPRAPIWordpress();

		// Don't push stories to the NPR story API if they were originally pulled from the NPR Story API
		$retrieved = get_post_meta( $post_ID, NPR_RETRIEVED_STORY_META_KEY, true );
		if ( empty( $retrieved ) || $retrieved == 0 ) {
			$api->send_request( $api->create_NPRML( $post ), $post_ID);
		} else {
			nprstory_error_log('Not pushing the story with post_ID ' . $post_ID . ' to the NPR Story API because it came from the API');
		}
	}
}

/**
 * Inform the NPR API that a post needs to be deleted.
 *
 * Limited to users that can delete other users' posts
 *
 * @param unknown_type $post_ID
 */
function nprstory_api_delete ( $post_ID ) {
	if ( ! current_user_can( 'delete_others_posts' ) ) {
		wp_die(
			__('You do not have permission to delete posts in the NPR API. Users that can delete other users\' posts have that ability: administrators and editors.'),
			__('NPR Story API Error'),
			403
		);
	}

	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty( $push_post_type ) ) {
		$push_post_type = 'post';
	}

	$api_id_meta = get_post_meta( $post_ID, NPR_STORY_ID_META_KEY );
	$api_id = $api_id_meta[0];
	$post = get_post( $post_ID );
	//if the push url isn't set, don't even try to delete.
	$push_url = get_option( 'ds_npr_api_push_url' );
	if ( $post->post_type == $push_post_type && ! empty( $push_url ) && ! empty( $api_id ) ) {
		// For now, only submit regular posts, and only on publish.
		if ( $post->post_type != 'post' || $post->post_status != 'publish' ) {
			return;
		}
		$api = new NPRAPIWordpress();
		$retrieved = get_post_meta( $post_ID, NPR_RETRIEVED_STORY_META_KEY, true );

		if ( empty( $retrieved ) || $retrieved == 0) {
			$api->send_request( $api->create_NPRML( $post ), $post_ID);
		} else {
			nprstory_error_log('Pushing delete action to the NPR Story API for the story with post_ID ' . $post_ID );
			$api->send_delete( $api_id );
		}
	}
}

/**
 * Register nprstory_npr_push and nprstory_npr_delete on appropriate hooks
 * this is where the magic happens
 */
if ( isset( $_POST['ds_npr_update_push'] ) ) {
	// No need to validate the ds_npr_update_push contents; we're checking only for its existence
	// permissions check is handled by nprstory_api_push
	add_action( 'save_post', 'nprstory_api_push', 10, 2 );
}
add_action( 'trash_post', 'nprstory_api_delete', 10, 2 );
//this may need to check version and use 'wp_trash_post'
add_action( 'wp_trash_post', 'nprstory_api_delete', 10, 2 );

/**
 *
 * define the option page for mapping fields
 */
function nprstory_push_add_field_mapping_page() {
    add_options_page(
        'NPR API Push Field Mapping',
        'NPR API Field Mapping',
        'manage_options',
        'ds_npr_api_push_mapping',
        'nprstory_add_field_mapping_page'
    );
}

add_action( 'admin_menu', 'nprstory_push_add_field_mapping_page' );

/**
 *
 * Callback for push mapping page
 */
function nprstory_api_push_mapping_callback() { }

/**
 *
 * Query the database for any meta fields for a post type, then store that in a WP transient/cache for a day.
 * I don't see the need for this cache to be any shorter, there's not a lot of adding of meta keys happening.
 * To clear this cache, after adding meta keys, you need to run delete_transient('ds_npr_' .  $post_type.'_meta_keys')
 * @param  $post_type
 */
function nprstory_push_meta_keys( $post_type = 'post' ) {
    global $wpdb;
    $limit = (int) apply_filters( 'postmeta_form_limit', 30 );
    $query = "
        SELECT DISTINCT( $wpdb->postmeta.meta_key )
        FROM $wpdb->posts
        LEFT JOIN $wpdb->postmeta
        ON $wpdb->posts.ID = $wpdb->postmeta.post_id
        WHERE $wpdb->posts.post_type = '%s'
        AND $wpdb->postmeta.meta_key != ''
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9]wp_.+$)'
        AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
    ";
    //AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)'
    $keys = $wpdb->get_col( $wpdb->prepare( $query, $post_type ) );
    if ( $keys ) natcasesort( $keys );

    //set_transient('ds_npr_' .  $post_type .'_meta_keys', $keys, 60*60*24); # 1 Day Expiration
    return $keys;
}

/**
 *
 * get the meta keys for a post type, they could be stored in a cache.
 *
 * @param  $post_type default is 'post'
 */
function nprstory_get_post_meta_keys( $post_type = 'post' ) {
    //$cache = get_transient('ds_npr_' .  $post_type .'_meta_keys');
    if ( ! empty( $cache ) ) {
    	$meta_keys = $cache;
    } else {
        $meta_keys = nprstory_push_meta_keys( $post_type );
    }
    return $meta_keys;
}

/**
 * checkbox validation callback
 * @see nprstory_push_settings_init
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_checkbox( $value ) {
	return ( $value ) ? true : false;
}
/**
 * Select validation callback
 * @see nprstory_push_settings_init
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_select( $value ) {
	// this value must be suitable for use as a form value
	return esc_attr( $value );
}
/**
 * url validation callback
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_url( $value ) {
	// because of the generic nature of this callback , it's not going to log anything, just do some sanitization
	// this value must be suitable for use as a form value
	return esc_attr( $value );
}
/**
 * NPR API Key validation callback
 * @see nprstory_push_settings_init
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_api_key( $value ) {
	return esc_attr( $value );
}
/**
 * URL validation callbacks for the API URLs
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_pull_url( $value ) {
	// Is this a URL? It better be a URL.
	if ( strpos( $value, 'http' ) !== 0 ) {
		add_settings_error(
			'ds_npr_api_pull_url',
			'not-http-url',
			esc_html( $value ) . __( ' is not a valid value for the NPR API Pull URL. It must be a URL starting with <code>http</code>.' ),
			'error'
		);
		$value = '';
	}
	return esc_attr( $value );
}
function nprstory_validation_callback_push_url( $value ) {
	// Is this a URL? It better be a URL.
	if ( strpos( $value, 'http' ) !== 0 ) {
		add_settings_error(
			'ds_npr_api_push_url',
			'not-http-url',
			esc_html( $value ) . __( ' is not a valid value for the NPR API Push URL. It must be a URL starting with <code>http</code>.' ),
			'error'
		);
		$value = '';
	}
	return esc_attr( $value );
}
/**
 * Org ID validation callbacks for the Org Id
 * @todo put this into use in nprstory_settings_init once we know for sure than an NPR Org ID is always a number
 * @see nprstory_settings_init
 */
function nprstory_validation_callback_org_id( $value ) {
	// Is this a number? it should be a number
	if ( ! is_numeric( $value ) ) {
		add_settings_error(
			'ds_npr_api_org_id',
			'not-http-url',
			esc_html( $value ) . __( ' is not a valid value for the NPR Organization ID. It must be a number.' ),
			'error'
		);
		$value = '';
	}
	return esc_attr( $value );
}

/**
 * callback for debugging validation callbacks
 * This should not be used in any released code
 */
function nprstory_validation_callback_debug( $value ) {
	error_log( var_export( $value, true ) ); // for debug use
	return $value;
}

/**
  Set up the fields for mapping custom meta fields to NRPML fields that we push to the API
*/
function nprstory_push_settings_init() {
    add_settings_section( 'ds_npr_push_settings', 'NPR API PUSH settings', 'nprstory_api_push_settings_callback', 'ds_npr_api_push_mapping' );

    add_settings_field( 'dp_npr_push_use_custom_map', 'Use Custom Settings', 'nprstory_api_use_custom_mapping_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'dp_npr_push_use_custom_map', 'nprstory_validation_callback_checkbox' );

    add_settings_field( 'ds_npr_api_mapping_title', 'Story Title', 'nprstory_api_mapping_title_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_title', 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_api_mapping_body', 'Story Body', 'nprstory_api_mapping_body_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_body' , 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_api_mapping_byline', 'Story Byline', 'nprstory_api_mapping_byline_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_byline' , 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_api_mapping_media_credit', 'Media Credit Field', 'nprstory_api_mapping_media_credit_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_media_credit' , 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_api_mapping_media_agency', 'Media Agency Field', 'nprstory_api_mapping_media_agency_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_media_agency' , 'nprstory_validation_callback_select');
    /**  This will add the mapping for media distribution.  But for now, hold off.
    add_settings_field( 'ds_npr_api_mapping_distribute_media', 'Distribute Media Field', 'nprstory_api_mapping_distribute_media_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_distribute_media' , 'nprstory_validation_callback_select');

    add_settings_field( 'ds_npr_api_mapping_distribute_media_polarity', 'Distribute Media Field Polarity', 'nprstory_api_mapping_distribute_media_polarity_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_distribute_media_polarity' , 'nprstory_validation_callback_select');
    //nprstory_api_mapping_distribute_media_polarity_callback
     *
     */
}

add_action( 'admin_init', 'nprstory_push_settings_init' );

/**
 * call back for push settings
 */
function nprstory_api_push_settings_callback() {
}

/**
 * callback for use custom mapping checkbox
 */
function nprstory_api_use_custom_mapping_callback(){
	$use_custom = get_option( 'dp_npr_push_use_custom_map' );
	$check_box_string = "<input id='dp_npr_push_use_custom_map' name='dp_npr_push_use_custom_map' type='checkbox' value='true'";

	if ( $use_custom ) {
		$check_box_string .= ' checked="checked" ';
	}
	$check_box_string .= "/>";
	$check_box_string .= wp_nonce_field( 'nprstory_nonce_dp_npr_push_use_custom_map', 'nprstory_nonce_dp_npr_push_use_custom_map_name', true, false );
	echo $check_box_string;
}

/**
 * callback for title mapping
 */
function nprstory_api_mapping_title_callback() {
	$push_post_type = nprstory_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	nprstory_show_keys_select( 'ds_npr_api_mapping_title', $keys );
}

/**
 * callback for body mapping
 */
function nprstory_api_mapping_body_callback() {
	$push_post_type = nprstory_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	nprstory_show_keys_select( 'ds_npr_api_mapping_body', $keys );
}

/**
 * callback for byline mapping
 */
function nprstory_api_mapping_byline_callback() {
	$push_post_type = nprstory_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	nprstory_show_keys_select( 'ds_npr_api_mapping_byline', $keys );
}

/**
 * callback for  media credit setting
 */
function nprstory_api_mapping_media_credit_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	nprstory_show_keys_select( 'ds_npr_api_mapping_media_credit', $keys );
}

/**
 * callback for  media agency setting
 */
function nprstory_api_mapping_media_agency_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	nprstory_show_keys_select( 'ds_npr_api_mapping_media_agency', $keys );
}

/**
 * callback for distribut media setting
 */
function nprstory_api_mapping_distribute_media_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	nprstory_show_keys_select( 'ds_npr_api_mapping_distribute_media', $keys );
}

function nprstory_api_mapping_distribute_media_polarity_callback() {
	echo "<div>Do Distribute or Do Not Distribute? <select id=ds_npr_api_mapping_distribute_media_polarity name=ds_npr_api_mapping_distribute_media_polarity>";

	$selected = get_option( 'ds_npr_api_mapping_distribute_media_polarity' );
	$keys = array( 1 => "DO Distribute", 0 => "DO NOT Dsitribute");
	foreach ( $keys as $i=>$key ) {
		$option_string = "\n<option  ";
		if ( $i == $selected ) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . $i . "'>" . esc_html( $key ) . " </option>";
		echo $option_string;
	}
	echo "</select> </div><p><hr></p>";
	wp_nonce_field( 'nprstory_nonce_ds_npr_api_mapping_distribute_media_polarity', 'nprstory_nonce_ds_npr_api_mapping_distribute_media_polarity_name', true, true );
}

/**
 *
 * create the select widget of all meta fields
 * @param  $field_name
 * @param  $keys
 */
function nprstory_show_keys_select( $field_name, $keys ) {

	$selected = get_option( $field_name );

	echo "<div><select id=" . $field_name . " name=" . $field_name . ">";

	echo '<option value="#NONE#"> &mdash; default &mdash; </option>';
	foreach ( $keys as $key ) {
		$option_string = "\n<option  ";
		if ($key == $selected) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . " </option>";
		echo $option_string;
	}
	echo "</select> </div>";
	wp_nonce_field( 'nprstory_nonce_' . $field_name, 'nprstory_nonce_' . $field_name . '_name', true, true );
}

function nprstory_get_push_post_type() {
	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty($push_post_type) ) {
		$push_post_type = 'post';
	}
	return $push_post_type;
}

function nprstory_get_permission_groups(){
    $perm_groups = '';
	//query the API for the lists for this org.
	$perm_url = get_option( 'ds_npr_api_push_url' ) . '/orgs/' . get_option( 'ds_npr_api_org_id' ) . '/groups' . '?apiKey=' . get_option('ds_npr_api_key');
	$http_result = wp_remote_get( $perm_url );
	if( ! is_wp_error( $http_result ) ) {
		$perm_groups_objs = json_decode( $http_result['body'] );
		if ( ! empty($perm_groups_objs) && ! isset( $perm_groups_objs->error ) ) {
			foreach( $perm_groups_objs as $pg ) {
				$perm_groups[$pg->group_id]['name'] = $pg->name;
			}
		}
	} else {
		$perm_groups = null;
	}
	//var_dump($perm_groups);
	//exit;
	return $perm_groups;
}

//add the bulk action to the dropdown on the post admin page
add_action('admin_footer-edit.php', 'nprstory_bulk_action_push_dropdown');

function nprstory_bulk_action_push_dropdown() {
	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty( $push_post_type ) ) {
		$push_post_type = 'post';
	}

	$push_url = get_option( 'ds_npr_api_push_url' );
    global $post_type;

    //make sure we have the right post_type and that the push URL is filled in, so we know we want to push this post-type
    if ( $post_type == $push_post_type && ! empty( $push_url ) ) {
    ?>
    <script type="text/javascript">
      jQuery(document).ready(function() {
    	  jQuery('<option>').val('pushNprStory').text('<?php _e('Push Story to NPR')?>').appendTo("select[name='action']");
        jQuery('<option>').val('pushNprStory').text('<?php _e('Push Story to NPR')?>').appendTo("select[name='action2']");
      });

    </script>
    <?php
    }
}

//do the new bulk action
add_action( 'load-edit.php', 'nprstory_bulk_action_push_action' );

function nprstory_bulk_action_push_action() {
    // 1. get the action
    $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
    $action = $wp_list_table->current_action();
    switch($action) {
        // 3. Perform the action
        case 'pushNprStory':

            // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if ( isset( $_REQUEST['post'] ) ) {
				$post_ids = array_map( 'intval', $_REQUEST['post'] );
			}

			//only export 20 at a time.
			//TODO : can we indicate on the screen what's been exported already?  that'd be tough.
            $exported = 0;
            foreach( $post_ids as $post_id ) {
                $api_id = get_post_meta( $post_id, NPR_STORY_ID_META_KEY, TRUE );
                //if this story doesn't have an API ID, push it to the API.
                if ( empty( $api_id ) && $exported < 20 ) {
                    $post = get_post( $post_id );
                    nprstory_api_push( $post_id, $post );
                    $exported ++;
                }
            }

            // build the redirect url
            //$sendback = add_query_arg( array('exported' => $exported, 'ids' => join(',', $post_ids) ), $sendback );
            break;
        default: return;
    }

    // ...

    // 4. Redirect client
    //wp_redirect($sendback);
    //exit();
}

/**
 * Save the "send to the API" metadata
 *
 * The meta name here is '_send_to_nprone' for backwards compatibility with plugin versions 1.6 and prior
 *
 * @param Int $post_ID The post ID of the post we're saving
 * @since 1.6 at least
 * @see nprstory_publish_meta_box
 */
function nprstory_save_send_to_api( $post_ID ) {
	// safety checks
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
	if ( ! current_user_can( 'edit_page', $post_ID ) ) return false;
	if ( empty( $post_ID ) ) return false;

	global $post;

	if ( get_post_type($post) != get_option('ds_npr_push_post_type') ) return false;
	$value = ( isset( $_POST['send_to_api'] ) && $_POST['send_to_api'] == 1 ) ? 1 : 0;

	// see historical note
	update_post_meta( $post_ID, '_send_to_nprone', $value );
}
add_action( 'save_post', 'nprstory_save_send_to_api');

/**
 * Save the "Send to NPR One" metadata
 *
 * If the send_to_api value is falsy, then this should not be saved as truthy
 *
 * @param Int $post_ID The post ID of the post we're saving
 * @since 1.7
 */
function nprstory_save_send_to_one( $post_ID ) {
	// safety checks
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
	if ( ! current_user_can( 'edit_page', $post_ID ) ) return false;
	if ( empty( $post_ID ) ) return false;

	global $post;

	if ( get_post_type($post) != get_option('ds_npr_push_post_type') ) return false;
	$value = (
		isset( $_POST['send_to_one'] )
		&& $_POST['send_to_one'] == 1
		&& isset( $_POST['send_to_api'] )
		&& $_POST['send_to_api'] == 1
	) ? 1 : 0;
	update_post_meta( $post_ID, '_send_to_one', $value );
}
add_action( 'save_post', 'nprstory_save_send_to_one');

/**
 * Save the "NPR One Featured" metadata
 *
 * If the send_to_one value is falsy, then this should not be saved as truthy
 * And thus, if the send_to_api value is falsy, then this should not be saved as truthy
 *
 * @param Int $post_ID The post ID of the post we're saving
 * @since 1.7
 */
function nprstory_save_nprone_featured( $post_ID ) {
	// safety checks
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
	if ( ! current_user_can( 'edit_page', $post_ID ) ) return false;
	if ( empty( $post_ID ) ) return false;

	global $post;

	if ( get_post_type($post) != get_option('ds_npr_push_post_type') ) return false;
	$value = (
		isset( $_POST['nprone_featured'] )
		&& $_POST['nprone_featured'] == 1
		&& isset( $_POST['send_to_api'] )
		&& $_POST['send_to_api'] == 1
		&& isset( $_POST['send_to_one'] )
		&& $_POST['send_to_one'] == 1
	) ? 1 : 0;
	update_post_meta( $post_ID, '_nprone_featured', $value );
}
add_action( 'save_post', 'nprstory_save_nprone_featured');

/**
 * Save the NPR One expiry datetime
 *
 * The meta name here is '_nprone_expiry_8601', and is saved in the ISO 8601 format for ease of conversion, not including the datetime.
 *
 * @param Int $post_ID The post ID of the post we're saving
 * @since 1.7
 * @see nprstory_publish_meta_box
 * @link https://en.wikipedia.org/wiki/ISO_8601
 */
function nprstory_save_datetime( $post_ID ) {
	// safety checks
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
	if ( ! current_user_can( 'edit_page', $post_ID ) ) return false;
	if ( empty( $post_ID ) ) return false;

	global $post;

	if ( get_post_type($post) != get_option('ds_npr_push_post_type') ) return false;

	$date = ( isset( $_POST['nprone-expiry-datepicker'] ) ) ? sanitize_text_field( $_POST['nprone-expiry-datepicker'] ): '';
	$time = ( isset( $_POST['nprone-expiry-time'] ) ) ? sanitize_text_field( $_POST['nprone-expiry-time'] ): '00:00';

	// If the post is not published and values are not set, save an empty post meta
	if ( isset( $date ) && 'publish' === $post->status ) {
		$timezone = get_option( 'gmt_offset' );
		$datetime = date_create( $date, new DateTimeZone( $timezone ) );
		$time = explode( ':', $time );
		$datetime->setTime( $time[0], $time[1] );
		$value = date_format( $datetime , DATE_ATOM );
		update_post_meta( $post_ID, '_nprone_expiry_8601', $value );
	} else {
		delete_post_meta( $post_ID, '_nprone_expiry_8601' );
	}
}
add_action( 'save_post', 'nprstory_save_datetime');

/**
 * Helper function to get the post expiry datetime
 *
 * The datetime is stored in post meta _nprone_expiry_8601
 * This assumes that the post has been published
 *
 * @param WP_Post|int $post the post ID or WP_Post object
 * @return DateTime the DateTime object created from the post expiry date
 * @see note on DATE_ATOM and DATE_ISO8601 https://secure.php.net/manual/en/class.datetime.php#datetime.constants.types
 * @since 1.7
 * @todo rewrite this to use fewer queries, so it's using the WP_Post internally instead of the post ID
 */
function nprstory_get_post_expiry_datetime( $post ) {
	$post = ( $post instanceof WP_Post ) ? $post->ID : $post ;
	$iso_8601 = get_post_meta( $post, '_nprone_expiry_8601', true );
	$timezone = get_option( 'gmt_offset' );

	if ( empty( $iso_8601 ) ) {
		// return DateTime for the publish date plus seven days
		$future = get_the_date( DATE_ATOM, $post ); // publish date
		return date_add( date_create( $future, new DateTimeZone( $timezone ) ), new DateInterval( 'P7D' ) );
	} else {
		// return DateTime for the expiry date
		return date_create( $iso_8601, new DateTimeZone( $timezone ) );
	}
}

/**
 * Add an admin notice to the post editor with the post's error message if it exists
 */
function nprstory_post_admin_message_error() {
	// only run on a post edit page
	$screen = get_current_screen();
	if ($screen->id !== 'post' ) {
		return;
	}

	// Push errors are saved in this piece of post meta, and there may not ba just one
	$errors = get_post_meta(get_the_ID(), NPR_PUSH_STORY_ERROR);

	if ( !empty( $errors ) ) {
		$errortext = '';
		foreach ( $errors as $error ) {
			$errortext .= sprintf(
				'<p>%1$s</p>',
				$error
			);
		}

		printf(
			'<div class="%1$s"><p>%2$s</p>%3$s</div>',
			'notice notice-error',
			__('An error occurred when pushing this post to NPR:'),
			$errortext
		);
	}
}
add_action( 'admin_notices', 'nprstory_post_admin_message_error' );

/**
 * Edit the post admin notices to include the post's id when it has been pushed successfully
 */
function nprstory_post_updated_messages_success( $messages ) {
	$id = get_post_meta(get_the_ID(), NPR_STORY_ID_META_KEY, true); // single

	if ( !empty($id) ) {

		// what do we call this thing?
		$post_type = get_post_type( get_the_ID() );
		$obj = get_post_type_object( $post_type );
		$singular = $obj->labels->singular_name;

		// Create the message about the thing being updated
		$messages['post'][4] = sprintf(
			__( '%s updated. This post\'s NPR ID is %s. ' ),
			esc_attr( $singular ),
			(string) $id
		);
	}
	return $messages;
}
add_filter( 'post_updated_messages', 'nprstory_post_updated_messages_success' );
