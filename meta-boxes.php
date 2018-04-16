<?php
/**
 * File containing meta box callback functions
 */

/**
 * Output the NPR Story API publishing options metabox for the edit page admin interface
 *
 * @param WP_Post $post the WordPress post object.
 * @see nprstory_save_send_to_api
 * @see nprstory_save_send_to_org
 * @see nprstory_save_send_to_one
 * @see nprstory_save_nprone_featured
 *
 * @todo When there is better browser support for input type="datetime-local", replace the jQuery UI and weird forms with the html5 solution. https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
 */
function nprstory_publish_meta_box( $post ) {
	$is_disabled = ( 'publish' !== $post->post_status );
	$attrs = array( 'id' => 'ds-npr-update-push' );

	if (  $is_disabled ) {
		$attrs['disabled'] = 'disabled';
	}

	wp_enqueue_style( 'jquery-ui' );
	wp_enqueue_style( 'nprstory_publish_meta_box_stylesheet' );
	wp_enqueue_script( 'nprstory_publish_meta_box_script' );

	?>
	<div id="ds-npr-publish-actions">
		<ul>
		<?php
			// send to the npr api
			// The meta name here is '_send_to_nprone' for backwards compatibility with plugin versions 1.6 and prior
			$nprapi = get_post_meta( $post->ID, '_send_to_nprone', true ); // 0 or 1
			if ( '0' !== $nprapi && '1' !== $nprapi ) { $nprapi = 1; } // defaults to checked; unset on new posts

			// this list item contains all other list items, because their enabled/disabled depends on this checkbox
			echo '<li>';
			printf(
				'<label><input value="1" type="checkbox" name="send_to_api" id="send_to_api" %2$s/> %1$s</label>',
				__( 'Send to NPR API', 'nprapi' ),
				checked( $nprapi, '1', false )
				// @see nprstory_save_send_to_api for a historical note on this metadata name
			);

			echo '<ul>';

			// send to npr dot org
			printf(
				'<li><label><input value="1" type="checkbox" name="send_to_org" id="send_to_org" %2$s/> %1$s</label></li>',
				__( 'Include for reading on NPR.org', 'nprapi' ),
				checked( get_post_meta( $post->ID, '_send_to_org', true ), '1', false )
			);

			// send to nprone
			printf(
				'<li><label><input value="1" type="checkbox" name="send_to_one" id="send_to_one" %2$s/> %1$s</label> %3$s </li>',
				__( 'Include for listening in NPR One', 'nprapi' ),
				checked( get_post_meta( $post->ID, '_send_to_one', true ), '1', false ),
				// the following is an ul li within the "Send to npr one" li
				// set the story as featured in NPR One
				sprintf(
					'<ul><li><label><input value="1" type="checkbox" name="nprone_featured" id="nprone_featured" %2$s/> %1$s</label></li></ul>',
					__( 'Set as featured story in NPR One', 'nprapi' ),
					checked( get_post_meta( $post->ID, '_nprone_featured', true ), '1', false )
				)
			);
			echo '</li>'; // end the "Send to NPR API" list item

		?>
		</ul>
	</div>
	<?php
	/*
	 * this section is only enabled if "include for listening in NPR One" is checked!
	 * This section does not use https://developer.wordpress.org/reference/functions/touch_time/ because there does not seem to be a way to pass it a custom element
	 */
	
	$datetime = nprstory_get_post_expiry_datetime( $post );
	?>
	<div id="nprone-expiry">
		<div id="nprone-expiry-display">
			<span >Expires on:</span>
			<?php
				printf(
					'<time style="font-weight: bold;">%1$s</time>',
					date_format( $datetime, 'M j, Y @ H:i' ) // Nov 30, 2017 @ 20:45
				);
			?>
			<button id="nprone-expiry-edit" class="link-effect"><?php esc_html_e( 'Edit', 'nprapi' ); ?></button>
		</div>
		<div id="nprone-expiry-form" class="hidden">
			<?php
				printf(
					'<input type="date" id="nprone-expiry-datepicker" size="10" placeholder="YYYY-MM-DD" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="%1$s"/>',
					date_format( $datetime, 'Y-m-d' ) // 2017-01-01
				);
				printf(
					'<input type="time" id="nprone-expiry-hour" size="5" placeholder="HH:MM" pattern="[0-9]{2}:[0-9]{2}" value="%1$s"/>',
					date_format( $datetime, 'H:i' ) // 2017-01-01
				);
			?>

			<div class="row">
				<button id="nprone-expiry-ok" class="button"><?php esc_html_e( 'OK', 'nprapi' ); ?></button>
				<button id="nprone-expiry-cancel" class="link-effect"><?php esc_html_e( 'cancel', 'nprapi' ); ?></button>
			</div>
		</div>
	</div>
<?php
}

/**
 * Register stylesheet for the NPR Story API publishing options metabox
 */
function nprstory_publish_meta_box_assets() {
	wp_register_style(
		'nprstory_publish_meta_box_stylesheet',
		NPRSTORY_PLUGIN_URL . 'assets/css/meta-box.css'
	);
	wp_register_style(
		'jquery-ui',
		NPRSTORY_PLUGIN_URL . 'assets/css/jquery-ui.css'
	);
	wp_register_script(
		'nprstory_publish_meta_box_script',
		NPRSTORY_PLUGIN_URL . 'assets/js/meta-box.js',
		array( 'jquery', 'jquery-ui-datepicker' ),
		null,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'nprstory_publish_meta_box_assets' );
