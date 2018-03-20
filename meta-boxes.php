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

	?>
	<div id="ds-npr-publish-actions">
		<ul>
		<?php
			// send to the npr api
			$nprapi = get_post_meta( $post->ID, '_send_to_nprone', true ); // 0 or 1
			if ( '0' !== $nprapi && '1' !== $nprapi ) { $nprapi = 1; } // defaults to checked; unset on new posts
			printf(
				'<li><label><input value="1" type="checkbox" name="send_to_api" id="send_to_api" %2$s/> %1$s</label></li>',
				__( 'Send to NPR API', 'nprapi' ),
				checked( $nprapi, '1', false )
				// @see nprstory_save_send_to_api for a historical note on this metadata name
			);

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

		?>
		</ul>
		<hr>
		<p class="helper-text"><?php echo wp_kses_post( $helper_text ); ?></p>
		<?php
			submit_button(
				__( 'Push to NPR', 'nprapi' ),
				'large',
				'ds_npr_update_push',
				false,
				$attrs
			);
		?>
	</div>
<?php
}

/**
 * Register stylesheet for the NPR Story API publishing options metabox
 */
function nprstory_publish_meta_box_styles() {
	wp_register_style(
		'nprstory_publish_meta_box_stylesheet',
		NPRSTORY_PLUGIN_URL . 'assets/css/meta-box.css'
	);
}
add_action( 'admin_enqueue_scripts', 'nprstory_publish_meta_box_styles' );
