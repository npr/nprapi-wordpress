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
define( 'NPR_IMAGE_GALLERY_META_KEY', '_npr_image_gallery');
define( 'NPR_AUDIO_META_KEY', '_npr_audio');

require_once( 'settings.php' );
require_once('classes/NPRAPIWordpress.php');

require_once('get_stories.php');
