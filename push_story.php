<?php

require_once ( 'classes/NPRAPIWordpress.php' );

/**
 *
 * push the contents and fields for a post to the NPR API
 * @param unknown_type $post_ID
 * @param unknown_type $post
 */
function nprstory_api_push ( $post_ID, $post ) {
	// @todo this needs to check that the current user is permitted to push to the API
	//
	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty( $push_post_type ) ) {
		$push_post_type = 'post';
	}

	//if the push url isn't set, don't even try to push.
	$push_url = get_option( 'ds_npr_api_push_url' );

	if ( ! empty ($push_url) ) {
		// For now, only submit regular posts, and only on publish.
		if ( $post->post_type != $push_post_type || $post->post_status != 'publish' ) {
			return;
		}

		//we may be able to have a custom body, so we need to check for that.
		$content = $post->post_content;
		$use_custom = get_option( 'dp_npr_push_use_custom_map' );
		$body_field = 'Body';
        if ($use_custom) {
	       //get the list of metas available for this post
	       $post_metas = get_post_custom_keys( $post->ID );

	       $custom_content_meta = get_option( 'ds_npr_api_mapping_body' );
	       $body_field = $custom_content_meta;
	           if ( ! empty( $custom_content_meta ) && $custom_content_meta != '#NONE#' && in_array( $custom_content_meta, $post_metas ) ) {
	           $content = get_post_meta( $post->ID, $custom_content_meta, true );
	       }
        }

		// Abort pushing to NPR if the post has no content
		if ( empty( $content ) ) {
			update_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, $body_field . ' is required for a post to be pushed to the NPR API.' );
			return;
		} else {
			delete_post_meta( $post_ID, NPR_PUSH_STORY_ERROR, $body_field . ' is required for a post to be pushed to the NPR API.' );
		}

		$api = new NPRAPIWordpress();

		// Don't push stories to the NPR story API if they were originally pulled from the NPR Story API
		$retrieved = get_post_meta( $post_ID, NPR_RETRIEVED_STORY_META_KEY, true );
		if ( empty( $retrieved ) || $retrieved == 0 ) {
			$api->send_request( $api->create_NPRML( $post ), $post_ID);
		} else {
			//error_log('Not pushing the story because it came from the API');
		}
	}
}

/**
 *
 * Inform the NPR API that a post needs to be deleted.
 * @param unknown_type $post_ID
 */
function nprstory_api_delete ( $post_ID ) {
	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty( $push_post_type ) ) {
		$push_post_type = 'post';
	}

	$api_id_meta = get_post_meta( $post_ID, NPR_STORY_ID_META_KEY );
	$api_id = $api_id_meta[0];
	$post = get_post($post_ID);
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
			//error_log('Not pushing the story because it came from the API');
			$api->send_delete( $api_id );
		}
	}
}

/**
 * Register nprstory_npr_push and nprstory_npr_delete on appropriate hooks
 * this is where the magic happens
 */
if ( isset( $_POST['ds_npr_update_push'] ) ) {
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
        'ds_npr_add_field_mapping_page'
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
        SELECT DISTINCT($wpdb->postmeta.meta_key)
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
    if ( $keys ) natcasesort($keys);

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
  Set up the fields for mapping custom meta fields to NRPML fields that we push to the API
*/
function nprstory_push_settings_init() {
    add_settings_section( 'ds_npr_push_settings', 'NPR API PUSH settings', 'nprstory_api_push_settings_callback', 'ds_npr_api_push_mapping' );

    add_settings_field( 'dp_npr_push_use_custom_map', 'Use Custom Settings', 'ds_npr_api_use_custom_mapping_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'dp_npr_push_use_custom_map' );

    add_settings_field( 'ds_npr_api_mapping_title', 'Story Title', 'ds_npr_api_mapping_title_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_title' );

    add_settings_field( 'ds_npr_api_mapping_body', 'Story Body', 'ds_npr_api_mapping_body_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_body' );

    add_settings_field( 'ds_npr_api_mapping_byline', 'Story Byline', 'ds_npr_api_mapping_byline_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_byline' );

    add_settings_field( 'ds_npr_api_mapping_media_credit', 'Media Credit Field', 'ds_npr_api_mapping_media_credit_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_media_credit' );

    add_settings_field( 'ds_npr_api_mapping_media_agency', 'Media Agency Field', 'ds_npr_api_mapping_media_agency_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_media_agency' );
    /**  This will add the mapping for media distribution.  But for now, hold off.
    add_settings_field( 'ds_npr_api_mapping_distribute_media', 'Distribute Media Field', 'ds_npr_api_mapping_distribute_media_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_distribute_media' );

    add_settings_field( 'ds_npr_api_mapping_distribute_media_polarity', 'Distribute Media Field Polarity', 'ds_npr_api_mapping_distribute_media_polarity_callback', 'ds_npr_api_push_mapping', 'ds_npr_push_settings' );
    register_setting( 'ds_npr_api_push_mapping', 'ds_npr_api_mapping_distribute_media_polarity' );
    //ds_npr_api_mapping_distribute_media_polarity_callback
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
function ds_npr_api_use_custom_mapping_callback(){
	$use_custom = get_option( 'dp_npr_push_use_custom_map' );
	$check_box_string = "<input id='dp_npr_push_use_custom_map' name='dp_npr_push_use_custom_map' type='checkbox' value='true'";

	if ( $use_custom ) {
		$check_box_string .= ' checked="checked" ';
	}
	$check_box_string .= "/>";
	echo $check_box_string;
}

/**
 * callback for title mapping
 */
function ds_npr_api_mapping_title_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_title', $keys );
}

/**
 * callback for body mapping
 */
function ds_npr_api_mapping_body_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_body', $keys );
}

/**
 * callback for byline mapping
 */
function ds_npr_api_mapping_byline_callback() {
	$push_post_type = ds_npr_get_push_post_type();
	$keys = nprstory_get_post_meta_keys( $push_post_type );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_byline', $keys );
}

/**
 * callback for  media credit setting
 */
function ds_npr_api_mapping_media_credit_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_media_credit', $keys );
}

/**
 * callback for  media agency setting
 */
function ds_npr_api_mapping_media_agency_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_media_agency', $keys );
}

/**
 * callback for distribut media setting
 */
function ds_npr_api_mapping_distribute_media_callback() {
	$keys = nprstory_get_post_meta_keys( 'attachment' );
	ds_npr_show_keys_select( 'ds_npr_api_mapping_distribute_media', $keys );
}

function ds_npr_api_mapping_distribute_media_polarity_callback() {
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
}

/**
 *
 * create the select widget of all meta fields
 * @param  $field_name
 * @param  $keys
 */
function ds_npr_show_keys_select( $field_name, $keys ) {

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

}

function ds_npr_get_push_post_type() {
	$push_post_type = get_option( 'ds_npr_push_post_type' );
	if ( empty($push_post_type) ) {
		$push_post_type = 'post';
	}
	return $push_post_type;
}

function ds_npr_get_permission_groups(){
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
add_action('admin_footer-edit.php', 'ds_npr_bulk_action_push_dropdown');

function ds_npr_bulk_action_push_dropdown() {
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
add_action( 'load-edit.php', 'ds_npr_bulk_action_push_action' );

function ds_npr_bulk_action_push_action() {
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

add_action( 'post_submitbox_misc_actions', 'send_to_nprone' );
function send_to_nprone() {
    global $post;
    if ( get_post_type( $post ) == get_option( 'ds_npr_push_post_type' ) ) {
        $value = get_post_meta( $post->ID, '_send_to_nprone', true );
        $checked = ! empty( $value ) ? ' checked="checked" ' : '';
        echo '<div class="misc-pub-section misc-pub-section-last"><input type="checkbox"' . $checked . 'value="1" name="send_to_nprone" /><label for="send_to_nprone">Send to NPR One</label></div>';
    }
}

add_action( 'save_post', 'save_send_to_nprone');
function save_send_to_nprone( $post_ID ) {
    global $post;
    if ( get_post_type($post) != get_option('ds_npr_push_post_type') ) return false;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
    if ( ! current_user_can( 'edit_page', $post_ID ) ) return false;
    if ( empty( $post_ID ) ) return false;
    $value = ($_POST['send_to_nprone']) ? 1 : 0;
    update_post_meta( $post_ID, '_send_to_nprone', $value );
}

/**
 * Add an admin notice to the post editor with the post's error message if it exists
 */
function ds_npr_post_admin_message_error() {
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
add_action( 'admin_notices', 'ds_npr_post_admin_message_error' );

/**
 * Edit the post admin notices to include the post's id when it has been pushed successfully
 */
function ds_npr_post_updated_messages_success( $messages ) {
	$id = get_post_meta(get_the_ID(), NPR_STORY_ID_META_KEY, true); // single

	if ( !empty($id) ) {
		$post_type = get_post_type( get_the_ID() );
		$obj = get_post_type_object( $post_type );
		$singular = $obj->labels->singular_name;

		$messages['post'][4] = sprintf(
			__( '%s updated. This post\'s NPR ID is %s. ' ),
			esc_attr( $singular ),
			strtolower( $singular ),
			(string) $id
		);
	}
	return $messages;
}
add_filter( 'post_updated_messages', 'ds_npr_post_updated_messages_success' );
