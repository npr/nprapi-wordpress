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
 */
function nprstory_publish_meta_box( $post ) {
	$helper_text = __( 'Push this story to NPR:', 'nprapi' );
	$is_disabled = ( 'publish' !== $post->post_status );
	$attrs = array( 'id' => 'ds-npr-update-push' );

	if ( $is_disabled ) {
		$helper_text = __( 'Publish this story in order to push it to NPR.', 'nprapi' );
	}

	if (  $is_disabled ) {
		$attrs['disabled'] = 'disabled';
	}

	wp_enqueue_style( 'nprstory_publish_meta_box_stylesheet' );
	wp_enqueue_script( 'nprstory_publish_meta_box_script' );

	?>
	<div id="ds-npr-publish-actions">
		<ul>
		<?php
			// send to the npr api
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
	<!--
		this section is only enabled if "include for listening in NPR One" is checked!
	-->
	<div id="nprone-expiry">
		<div id="nprone-expiry-display">
			<span >Expires on:</span>
			<time style="font-weight: bold;">Nov 30, 2017 @ 20:45</time>
			<button id="nprone-expiry-edit" class="link-effect"><?php esc_html_e( 'Edit', 'nprapi' ); ?></button>
		</div>
		<div id="nprone-expiry-form" class="hidden">
			<select id="nprone-expiry-month" name="nprone-expiry-month">
				<option value="1">1-Jan</option>
				<option value="2">2-Feb</option>
				<option value="3">3-Mar</option>
				<option value="4">4-Apr</option>
				<option value="5">5-May</option>
				<option value="6">6-Jun</option>
				<option value="7">7-Jul</option>
				<option value="8">8-Aug</option>
				<option value="9">9-Sept</option>
				<option value="10">10-Oct</option>
				<option selected value="11">11-Nov</option>
				<option value="12">12-Dec</option>
			</select>
			<input id="nprone-expiry-day" name="nprone-expiry-day" type="number" min="1" max="32" value="30" style="width:2.5em;" />,
			<input id="nprone-expiry-year" name="nprone-expiry-year" type="number" min="1970" value="2018" style="width:4em;"/>
			@
			<input id="nprone-expiry-hour" name="nprone-expiry-hour" type="number" min="0" max="25" value="20" style="width:2.5em;"/>:<input id="nprone-expiry-minute" name="nprone-expiry-minute" type="number" min="0" max="60" value="45" style="width:2.5em;"/>

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
	wp_register_script(
		'nprstory_publish_meta_box_script',
		NPRSTORY_PLUGIN_URL . 'assets/js/meta-box.js',
		array( 'jquery', 'jquery-ui-datepicker' ),
		null,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'nprstory_publish_meta_box_assets' );
