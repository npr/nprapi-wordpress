<?php
/**
 * Plugin Name: WP DS NPR API
 * Description: A collection of tools for reusing content from NPR.org supplied by Digital Services.
 * Version: 0.1
 * Author: Kevin Moylan
 * License: GPLv2
*/
/*
    Copyright 2012 NPR Digital Services

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'NPR_STORY_ID_META_KEY', 'npr_story_id' );
define( 'NPR_API_LINK_META_KEY', 'npr_api_link' );
define( 'NPR_HTML_LINK_META_KEY', 'npr_html_link' );
define( 'NPR_SHORT_LINK_META_KEY', 'npr_short_link' );
define( 'NPR_STORY_CONTENT_META_KEY', 'npr_story_content' );
define( 'NPR_BYLINE_META_KEY', 'npr_byline' );
define( 'NPR_IMAGE_GALLERY_META_KEY', 'npr_image_gallery');
define( 'NPR_AUDIO_META_KEY', 'npr_audio');
define( 'NPR_AUDIO_M3U_META_KEY', 'npr_audio_m3u');
define( 'NPR_PUB_DATE_META_KEY', 'npr_pub_date');
define( 'NPR_STORY_DATE_MEATA_KEY', 'npr_story_date');
define( 'NPR_LAST_MODIFIED_DATE_KEY', 'npr_last_modified_date');
define( 'NPR_RETRIEVED_STORY_META_KEY', 'npr_retrieved_story');
define( 'NPR_PUSH_STORY_ERROR', 'npr_push_story_error');

define('NPR_MAX_QUERIES', 10);

define('DS_NPR_PLUGIN_FILE', plugin_dir_path(__FILE__) );

define('NPR_POST_TYPE', 'npr_story_post');

require_once( 'settings.php' );
require_once('classes/NPRAPIWordpress.php');

require_once('get_stories.php');
//add the cron to get stories
register_activation_hook(WP_PLUGIN_DIR.'/WP-DS-NPR-API/ds-npr-api.php', 'ds_npr_story_activation');
add_action('npr_ds_hourly_cron', array ('DS_NPR_API','ds_npr_story_cron_pull'));
register_deactivation_hook(WP_PLUGIN_DIR.'/WP-DS-NPR-API/ds-npr-api.php', 'ds_npr_story_deactivation');


function ds_npr_story_activation() {		
	if ( !wp_next_scheduled( 'npr_ds_hourly_cron' ) ) {
		wp_schedule_event( time(), 'hourly', 'npr_ds_hourly_cron');
	}

	$num =  get_option('ds_npr_num');
	if (empty($num)){
		update_option('ds_npr_num', 5);
	}
	
	$def_url = 'http://api-s1.npr.org';
	$push_url = get_option( 'ds_npr_api_push_url' );
	if (empty($push_url)){
		update_option('ds_npr_api_push_url', $def_url);
	}
}
	
function ds_npr_story_deactivation() {
	wp_clear_scheduled_hook('npr_ds_hourly_cron');
	
	$num =  get_option('ds_npr_num');
	if (!empty($num)){
		delete_option('ds_npr_num');
	}
	
	$push_url = get_option( 'ds_npr_api_push_url' );
	if (!empty($push_url)){
		delete_option('ds_npr_api_push_url');
	}
}


function ds_npr_show_message($message, $errormsg = false)
{
	if ($errormsg) {
		echo '<div id="message" class="error">';
	}
	else {
		echo '<div id="message" class="updated fade">';
	}

	echo "<p><strong>$message</strong></p></div>";
}   
require_once('push_story.php');


add_action( 'init', 'ds_npr_create_post_type' );
function ds_npr_create_post_type() {
	register_post_type( NPR_POST_TYPE,
		array(
			'labels' => array(
				'name' => __( 'NPR Stories' ),
				'singular_name' => __( 'NPR Story' ),
			),
		'public' => true,
		'has_archive' => true,
		'menu_position' => 5,
		'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		)
	);
}
