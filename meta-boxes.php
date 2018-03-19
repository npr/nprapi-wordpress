<?php
/**
 * File containing meta box callback functions
 */

/**
 * Output the NPR Story API publishing options metabox for the edit page admin interface
 *
 * @param WP_Post $post the WordPress post object.
 */
function nprstory_publish_meta_box( $post ) {
	$helper_text = __( 'Push this story to NPR:', 'nprapi' );
	$is_disabled = ( 'post' !== $post->post_status );
	if ( $is_disabled ) {
		$helper_text = __( 'Publish this story in order to push it to NPR.', 'nprapi' );
	}
	$attrs = array( 'id' => 'ds-npr-update-push' );
	if (  $is_disabled ) {
		$attrs['disabled'] = 'disabled';
	}

	wp_enqueue_style( 'nprstory_publish_meta_box_stylesheet' );

	?>
	<div id="ds-npr-publish-actions">
		<ul>
		<?php
			printf(
				'<li><label><input value="" type="checkbox" name="ds_npr_update_push" id="ds_npr_update_push"/> %1$s</label></li>',
				__( 'Send to NPR API', 'nprapi' )
			);
			printf(
				'<li><label><input value="" type="checkbox" name="ds_npr_push_npr_org" id="ds_npr_push_npr_org"/> %1$s</label></li>',
				__( 'Include for reading on NPR.org', 'nprapi' )
			);
			printf(
				'<li><label><input value="" type="checkbox" name="ds_npr_push_npr_one" id="ds_npr_push_npr_one"/> %1$s</label></li>',
				__( 'Include for listening in NPR One', 'nprapi' )
			);
			printf(
				'<li><ul><li><label><input value="" type="checkbox" name="ds_npr_push_npr_one" id="ds_npr_push_npr_one"/> %1$s</label></li></ul></li>',
				__( 'Set as featured story in NPR One', 'nprapi' )
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
